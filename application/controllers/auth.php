<?php 
/**
 * Controller handling all auth activities, including registration and 
 * login / logout
 */
class Auth_Controller extends Controller
{ 
    protected $auto_render = TRUE;

    /**
     * Combination login / registration action.
     */
    public function index()
    {
    }

    /**
     * Convenience action to redirect to logged in user's default profile.
     */
    public function home()
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
    function register()
    {
        // Accept only POST requests for registration.
        if ('post' != request::method())
            return;

        $logins = new Logins_Model();
        $validator = $logins->getValidator($this->input->post());
        if (!$validator->validate()) {
            $this->setViewData('errors', 
                $validator->errors('form_errors_registration'));
            return;
        }

        $data = $validator->as_array();
        $new_login = $logins->registerWithProfile($data);

        $this->setViewData(array(
            'errors' => NULL,
            'form'   => $data
        ));

        return url::redirect('login');
    }

    /**
     * User login action.
     */
    public function login()
    {
        /*
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
        */
    }

    /**
     * User logout action.
     */
    public function logout()
    {
        /*
        // Clear the identity and remove it from the view.
        Zend_Auth::getInstance()->clearIdentity();
        $this->view->assign(array(
            'auth_identity' => null,
            'auth_profile'  => null
        ));
         */
    }
         
} 
