<?php 
/**
 * Controller handling all auth activities, including registration and 
 * login / logout
 */
class AuthController extends Zend_Controller_Action  
{ 
    private $_authAdapter = null;

    public function preDispatch()
    {
        $auth = Zend_Auth::getInstance();

        if ($auth->hasIdentity()) {
            if (!in_array($this->getRequest()->getActionName(), array('logout', 'home'))) {
                return $this->_helper->redirector->gotoRoute(
                    array(), 'auth_home'
                );
            }
        } else {
            if (!in_array($this->getRequest()->getActionName(), array('index', 'register', 'login'))) {
                return $this->_helper->redirector->gotoRoute(
                    array(), 'auth_login'
                );
            }
        }

        $this->view->login_form = $this->_helper->getForm(
            'login', 
            array(
                'action' => $this->view->url(
                    array(
                        'controller' => 'auth',
                        'action'     => 'login',
                    ),
                    'auth_login',
                    true
                ), 
            )
        );
        $this->view->registration_form = $this->_helper->getForm(
            'registration', 
            array(
                'action' => $this->view->url(
                    array(
                        'controller' => 'auth',
                        'action'     => 'register',
                    ),
                    'auth_register',
                    true
                ), 
            )
        );
    }

    /**
     * Combination login / registration action.
     */
    public function indexAction()
    {
    }

    /**
     * Convenience action to redirect to logged in user's default profile.
     */
    public function homeAction()
    {
        $logins   = $this->_helper->getModel('Logins');
        $identity = Zend_Auth::getInstance()->getIdentity();
        $profile  = $logins->fetchDefaultProfileForLogin($identity->id);

        if (empty($profile['screen_name'])) {
            return $this->_helper->redirector->gotoRoute(
                array('screen_name' => $profile['screen_name']),
                'auth_logout'
            );
        }

        return $this->_helper->redirector->gotoRoute(
            array('screen_name' => $profile['screen_name']),
            'post_profile'
        );
    }

    /**
     * New user registration action.
     */
    function registerAction()
    {
        $form = $this->view->registration_form;

        $request = $this->getRequest();
        if (!$this->getRequest()->isPost()) {
            return;
        }

        $post_data = $request->getPost();
        if (!$form->isValid($post_data)) {
            return;
        }

        $logins = $this->_helper->getModel('Logins');
        try {
            $new_login = $logins->registerWithProfile($form->getValues());
        } catch (Exception $e) {
            // TODO: Better error message
            $form->setDescription('Registration failed, please try again.');
            return;
        }

        // We're authenticated! Redirect to the user page
        return $this->_helper->redirector->gotoRoute(
            array(), 'auth_login'
        );
    }

    /**
     * User login action.
     */
    public function loginAction()
    {
        $form = $this->view->login_form;

        $request = $this->getRequest();
        if (!$request->isPost()) {
            $get_data = $request->getQuery();
            if (!empty($get_data['jump'])) {
                $form->populate(array(
                    'jump' => $get_data['jump']
                ));
            }
            return;
        }

        $post_data = $request->getPost();
        if (!$form->isValid($post_data)) {
            return;
        }

        // Get our authentication adapter and check credentials
        $auth        = Zend_Auth::getInstance();

        $form_values = $form->getValues();
        $adapter     = $this->getAuthAdapter($form_values);

        $storage = $auth->getStorage();
        $storage->setUserName($form_values['login_name']);

        $result  = $auth->authenticate($adapter);
        if (!$result->isValid()) {
            $form->setDescription('Login name and password not valid');
            return;
        }

        // Persist some identity details
        $logins_model = $this->_helper->getModel('Logins');
        $identity = $adapter->getResultRowObject(array(
            'id', 'login_name', 'email', 'created'
        ));
        $identity->default_profile = 
            $logins_model->fetchDefaultProfileForLogin($identity->id);
        $storage->write($identity);

        // We're authenticated!
        if (isset($post_data['jump']) && substr($post_data['jump'], 0, 1) == '/') {
            // Jump to the site-relative URL retained before login (ie. a 
            // populated bookmark form)
            return $this->_helper->redirector->gotoUrl(
                $post_data['jump'], array('prependBase' => true)
            );
        } else {
            // Jump to the profile home page.
            return $this->_helper->redirector->gotoRoute(
                array(), 'auth_home'
            );
        }
    }

    /**
     * User logout action.
     */
    public function logoutAction()
    {
        // Clear the identity and remove it from the view.
        Zend_Auth::getInstance()->clearIdentity();
        $this->view->assign(array(
            'auth_identity' => null,
            'auth_profile'  => null
        ));
    }

    function openidAction()
    {
        $status = "";
        $auth = Zend_Auth::getInstance();
        if ((isset($_POST['openid_action']) &&
            $_POST['openid_action'] == "login" &&
            !empty($_POST['openid_identifier'])) ||
            isset($_GET['openid_mode']) ||
            isset($_POST['openid_mode'])) {
                $result = $auth->authenticate(
                    new Zend_Auth_Adapter_OpenId(@$_POST['openid_identifier']));
                if ($result->isValid()) {
                    $status = "You are logged in as "
                        . $auth->getIdentity();
                } else {
                    $auth->clearIdentity();
                    foreach ($result->getMessages() as $message) {
                        $status .= "$message\n";
                    }
                }
            } else if ($auth->hasIdentity()) {
                if (isset($_POST['openid_action']) &&
                    $_POST['openid_action'] == "logout") {
                        $auth->clearIdentity();
                    } else {
                        $status = "You are logged in as ";
                            // . $auth->getIdentity();
                    }
            }
        $this->view->status = $status;

    }

    /**
     * Build a Zend auth adapter given a username and password pair.
     */
    public function getAuthAdapter($values)
    {
        if (null === $this->_authAdapter) {
            $this->_authAdapter = new Zend_Auth_Adapter_DbTable(
                Zend_Db_Table_Abstract::getDefaultAdapter(),
                'logins',
                'login_name',
                'password',
                '?' // AND (date_banned IS NULL)'
            );
        }
        $this->_authAdapter->setIdentity($values['login_name']);
        $this->_authAdapter->setCredential(md5($values['password']));
        return $this->_authAdapter;
    }
         
} 
