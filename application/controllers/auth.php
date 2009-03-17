<?php 
/**
 * Controller handling all auth activities, including registration and 
 * login / logout
 *
 * @author l.m.orchard <l.m.orchard@pobox.com>
 * @package Memex
 */
class Auth_Controller extends Local_Controller
{ 
    protected $auto_render = TRUE;

    /**
     * Combination login / registration action.
     */
    public function index()
    {
        return url::redirect('login');
    }

    /**
     * Convenience action to redirect to logged in user's default profile.
     */
    public function home()
    {
        $this->auto_render = false;
        if (!$this->auth->isLoggedIn()) {
            return url::redirect('login');
        } else {
            return url::redirect('people/' . 
                $this->auth_data['profile']['screen_name']);
        }
    }

    /**
     * New user registration action.
     */
    function register()
    {
        if ('post' != request::method())
            return;

        $logins    = new Logins_Model();
        $form_data = $this->input->post();
        $is_valid  = $logins->validateRegistration($form_data);

        $this->view->form_data = $form_data;

        if (!$is_valid) {
            $this->view->form_errors = 
                $form_data->errors('form_errors_auth');
            return;
        }

        $new_login = $logins->registerWithProfile($form_data);

        return url::redirect('login');
    }

    /**
     * User login action.
     */
    public function login()
    {
        if ('post' != request::method())
            return;

        $logins    = new Logins_Model();
        $form_data = $this->input->post();
        $is_valid  = $logins->validateLogin($form_data);

        $this->view->form_data = $form_data;

        if (!$is_valid) {
            $this->view->form_errors = 
                $form_data->errors('form_errors_auth');
            return;
        }

        $login   = $logins->fetchByLoginName($form_data['login_name']);
        $profile = $logins->fetchDefaultProfileForLogin($login['id']);

        $this->auth->login($form_data['login_name'], array(
            'login' => $login, 'profile' => $profile
        ));

        if (isset($form_data['jump']) && substr($form_data['jump'], 0, 1) == '/') {
            // Allow post-login redirect only if the param starts with '/', 
            // interpreted as relatve to root of site.
            return url::redirect($form_data['jump']);
        } else {
            return url::redirect('/home');
        }
    }

    /**
     * User logout action.
     */
    public function logout()
    {
        $this->view->set_global(array(
            'auth_login'   => null,
            'auth_profile' => null
        ));
        $this->auth->logout();
    }
         
} 
