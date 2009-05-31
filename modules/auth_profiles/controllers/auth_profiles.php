<?php 
/**
 * Controller handling all auth activities, including registration and 
 * login / logout
 *
 * @package    Memex
 * @subpackage controllers
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Auth_Profiles_Controller extends Local_Controller
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
        if (!AuthProfiles::is_logged_in()) {
            return url::redirect('login');
        } else {
            $auth_data = AuthProfiles::get_user_data();
            return url::redirect(sprintf(
                Kohana::config('auth_profiles.home_url'),
                AuthProfiles::get_profile('screen_name')
            ));
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

        AuthProfiles::login($form_data['login_name'], $login, $profile);

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
        AuthProfiles::logout();
    }

    /**
     * Profiles settings.
     */
    public function settings()
    {
        $params = $this->getParamsFromRoute(array());

        if ($params['screen_name'] != AuthProfiles::get_profile('screen_name')) {
            header("HTTP/1.1 403 Forbidden"); 
            exit;
        }

        // Set up initial whiteboard, fire off event to gather content from 
        // interested listeners.
        $data = array(
            'controller' => $this, 
            'sections'   => array()
        );
        Event::run('auth_profiles.before_settings_menu', $data);
        $this->view->sections = $data['sections'];
    }
         
} 