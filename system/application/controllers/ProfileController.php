<?php 
/**
 * Controller handling all auth activities, including registration and 
 * login / logout
 */
class ProfileController extends Zend_Controller_Action  
{ 

    public function preDispatch()
    {
        if (Zend_Auth::getInstance()->hasIdentity()) {
        } else {
            if (!in_array($this->getRequest()->getActionName(), array('index'))) {
                $this->_helper->redirector->gotoRoute(array(), 'auth_login');
            }
        }
    }

    /**
     * Initialize the controller.
     */
    public function init() {

    }

    /**
     * Index page of all profiles
     * TODO: People search?
     */
    public function indexAction() 
    { 
    } 

    /**
     * Profiles settings.
     */
    public function settingsAction()
    {
    }

    /**
     * Delicious account settings.
     * @TODO: See if there's some way to make this a part of Memex_Plugin_Delicious
     */
    public function settingsDeliciousAction()
    {
        $identity   = Zend_Auth::getInstance()->getIdentity();
        $profile_id = $identity->default_profile['id'];
        $request    = $this->getRequest();

        $this->view->form = $form = new Zend_Form();
        $form
            ->setAttrib('id', 'delicious_account')
            ->setMethod('post')
            ->addElementPrefixPath(
                'Memex_Validate', APPLICATION_PATH . '/models/Validate/', 
                'validate'
            )
            ->addElement('checkbox', 'enabled', array(
                'label'      => 'Enabled'
            ))
            ->addElement('text', 'user_name', array(
                'label'      => 'Login name',
                'required'   => true,
                'filters'    => array('StringTrim'),
                'validators' => array(
                    array('StringLength', false, array(1, 64))
                )
            ))
            ->addElement('password', 'password', array(
                'label'      => 'Password',
                'required'   => true,
                'filters'    => array('StringTrim'),
                'validators' => array(
                    array('StringLength', false, array(1, 255))
                )
            ))
            ->addElement('submit', 'save', array(
                'label' => 'save'
            ))
            ->addDisplayGroup(
                array('enabled', 'user_name', 'password', 'save'), 
                'delicious_account',
                array('legend' => 'delicious login details')
            )
            ->setDecorators(array(
                'FormElements',
                array('HtmlTag', array('tag' => 'dl', 'class' => 'zend_form')),
                array('Description', array('placement' => 'prepend')),
                'Form'
            ));

        $profiles_model = $this->_helper->getModel('Profiles');

        if (!$this->getRequest()->isPost()) {
            // For a GET request, try pre-populating the form with the existing 
            // profile settings.
            $existing = $profiles_model->getAttributes($profile_id, array(
                Memex_Plugin_Delicious::ENABLED,
                Memex_Plugin_Delicious::USER_NAME,
                Memex_Plugin_Delicious::PASSWORD
            ));
            $form->populate(array(
                'enabled' => 
                    @$existing[Memex_Plugin_Delicious::ENABLED],
                'user_name' => 
                    @$existing[Memex_Plugin_Delicious::USER_NAME],
                'password' =>
                    @$existing[Memex_Plugin_Delicious::PASSWORD]
            ));
            return;
        }

        $post_data = $request->getPost();
        if (!$form->isValid($post_data)) {
            // If the form validation fails, just punt.
            return;
        }

        // Attempt making an authenticated fetch against v1 del API
        $ch = curl_init('https://api.del.icio.us/v1/posts/update');
        curl_setopt_array($ch, array(
            CURLOPT_USERAGENT      => 'Memex/0.1',
            CURLOPT_FAILONERROR    => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERPWD => 
                $post_data['user_name'] . ':' . $post_data['password']
        ));
        $resp = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        // If the fetch wasn't successful, assume the username/password 
        // was wrong.
        if (200 != $info['http_code']) {
            $form->setDescription('User name and password invalid for delicious.com');
            return;
        } 

        // Update the profile settings.
        $profiles_model->setAttributes($profile_id, array(
            Memex_Plugin_Delicious::ENABLED => 
                !!$post_data['enabled'],
            Memex_Plugin_Delicious::USER_NAME => 
                $post_data['user_name'],
            Memex_Plugin_Delicious::PASSWORD => 
                $post_data['password']
        ));

        $form->setDescription('Settings updated, user name and password '.
            'accepted at delicious.com');
    }

} 
