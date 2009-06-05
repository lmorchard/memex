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
     * Basic overall controller preamble
     */
    public function __construct()
    {
        parent::__construct();

        $protected_methods = array(
            'home', 'changepassword'
        );
        if (!AuthProfiles::is_logged_in()) {
            $method = Router::$method;
            $reset_token = $this->input->get('password_reset_token');
            if ('changepassword' == $method && !empty($reset_token)) {
                // Change password is okay, if there's a reset token.
                return;
            }
            if (in_array($method, $protected_methods)) {
                return AuthProfiles::redirect_login();
            }
        }
    }

    /**
     * Combination login / registration action.
     */
    public function index()
    {
        return url::redirect('home');
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
        $is_valid  = $logins->validate_registration($form_data);

        $this->view->form_data = $form_data;

        if (!$is_valid) {
            $this->view->form_errors = 
                $form_data->errors('form_errors_auth');
            return;
        }

        $new_login = $logins->register_with_profile($form_data);

        return url::redirect('login');
    }

    /**
     * Change password for a login
     */
    public function changepassword()
    {
        $logins    = new Logins_Model();
        $form_data = $this->input->post();

        if (AuthProfiles::is_logged_in()) {
            // Logged in, so use auth login details.
            $login_id = AuthProfiles::get_login('id'); 
            $form_data['login_name'] = AuthProfiles::get_login('login_name');
        } else {
            // Not logged in, so try using the password reset token.
            $login = $logins->fetch_by_password_reset_token(
                $this->input->get('password_reset_token')
            );
            if (empty($login)) {
                // Nothing retrieved for the token, so complain.
                $this->view->invalid_reset_token = true;
                return;
            }
            $login_id = $login['id']; 
            $form_data['login_name'] = $login['login_name'];
        }

        // Jump straight to rendering if not a POST.
        if ('post' != request::method())
            return;

        // Validate the password change attempt.
        $is_valid  = $logins->validate_change_password($form_data);
        $this->view->form_data = $form_data;
        if (!$is_valid) {
            $this->view->form_errors = 
                $form_data->errors('form_errors_auth');
            return;
        }

        $logins->change_password(
            $login_id, 
            $form_data['new_password']
        );
        
        AuthProfiles::logout();
        $this->view->password_changed = true;
    }

    /**
     * Handle forgotten password issue.
     */
    public function forgotpassword() {
        if ('post' != request::method())
            return;

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
        $is_valid  = $logins->validate_login($form_data);

        $this->view->form_data = $form_data;

        if (!$is_valid) {
            $this->view->form_errors = 
                $form_data->errors('form_errors_auth');
            return;
        }

        $login   = $logins->fetch_by_login_name($form_data['login_name']);
        $profile = $logins->fetch_default_profile_for_login($login['id']);

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

        $u_name = rawurlencode(AuthProfiles::get_profile('screen_name'));

        // Set up initial whiteboard, fire off event to gather content from 
        // interested listeners.
        $data = array(
            'controller' => $this, 
            'sections'   => array(
                array(
                    'title' => 'Basics',
                    'priority' => 999,
                    'items' => array(
                        array(
                            'url' => "profiles/{$u_name}/settings/basics/details",
                            'title' => 'Edit profile details',
                            'description' => 'change screen name, bio, etc.'
                        ),
                        array(
                            'url' => "profiles/{$u_name}/settings/basics/changepassword",
                            'title' => 'Change login password',
                            'description' => 'change current login password'
                        ),
                        array(
                            'url' => "profiles/{$u_name}/settings/basics/logins",
                            'title' => 'Manage profile logins',
                            'description' => 'create and remove logins for this profile'
                        ),
                        array(
                            'url' => "profiles/{$u_name}/settings/basics/delete",
                            'title' => 'Delete profile',
                            'description' => 'delete this profile altogether'
                        ),
                    )
                )
            )
        );
        Event::run('auth_profiles.before_settings_menu', $data);
        $this->view->sections = $data['sections'];
    }
         
} 
