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
            'home', 'changeemail', 'logout'
        );
        if (!AuthProfiles::is_logged_in()) {
            if (in_array(Router::$method, $protected_methods)) {
                return AuthProfiles::redirect_login();
            }
        }

        $this->logins_model = new Logins_Model();
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
     * New user registration.
     */
    public function register()
    {
        $form_data = $this->validate_form(
            $this->logins_model, 
            'validate_registration', 'form_errors_auth'
        );
        if (null===$form_data) return;

        $new_login = $this->logins_model
            ->register_with_profile($form_data);

        return url::redirect('login');
    }

    /**
     * User login action.
     */
    public function login()
    {
        $form_data = $this->validate_form(
            $this->logins_model, 
            'validate_login', 'form_errors_auth'
        );
        if (null===$form_data) return;

        $login = $this->logins_model->
            fetch_by_login_name($form_data['login_name']);
        $profile = $this->logins_model->
            fetch_default_profile_for_login($login['id']);

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
     * Start email address change process.
     */
    public function changeemail()
    {
        $form_data = $this->validate_form(
            $this->logins_model, 
            'validate_change_email', 'form_errors_auth'
        );
        if (null===$form_data) return;

        $token = $this->logins_model->set_email_verification_token(
            AuthProfiles::get_login('id'),
            $form_data['new_email']
        );

        $this->view->email_verification_token_set = true;

        email::send_view(
            $form_data['new_email'],
            'auth_profiles/changeemail_email',
            array(
                'email_verification_token' => $token,
                'login_name' => AuthProfiles::get_login('login_name')
            )
        );
    }

    /**
     * Complete verification of a new email address.
     */
    public function verifyemail()
    {
        $token = ('post' == request::method()) ?
            $this->input->post('email_verification_token') :
            $this->input->get('email_verification_token');

        // Look up the login by token, and abort if not found.
        $login = $this->logins_model->fetch_by_email_verification_token($token);
        if (empty($login)) {
            $this->view->invalid_token = true;
            return;
        }

        $this->logins_model->change_email(
            $login['id'], $login['new_email']
        );
    }

    /**
     * Change password for a login
     */
    public function changepassword()
    {
        // Try accepting a reset token from either GET or POST.
        $reset_token = ('post' == request::method()) ?
            $this->input->post('password_reset_token') :
            $this->input->get('password_reset_token');

        if (empty($reset_token) && !AuthProfiles::is_logged_in()) {
        
            // If no token and not logged in, jump to login.
            return AuthProfiles::redirect_login();
        
        } elseif (empty($reset_token) && AuthProfiles::is_logged_in()) {
        
            // Logged in and no token, so use auth login details.
            $login_id = AuthProfiles::get_login('id'); 
        
        } else {
            
            // Look up the login by token, and abort if not found.
            $login = $this->logins_model->fetch_by_password_reset_token($reset_token);
            if (empty($login)) {
                $this->view->invalid_reset_token = true;
                return;
            }

            // Use the fetched login ID and toss name into view.
            $login_id = $login['id']; 
            $this->view->forgot_password_login_name = $login['login_name'];
            
            // Pre-emptively force logout in case current login and login 
            // associated with token differ.
            AuthProfiles::logout();

        }

        // Now that we know who's trying to change a password, validate the 
        // form appropriately
        $form_data = $this->validate_form(
            $this->logins_model, 
            empty($reset_token) ? 
                'validate_change_password' : 
                'validate_change_password_with_token', 
            'form_errors_auth'
        );
        if (null===$form_data) return;
        
        // Finally, perform the password change.
        $changed = $this->logins_model->change_password(
            $login_id, $form_data['new_password']
        );
        if (!$changed) {
            // Something unexpected happened.
            $this->view->password_change_failed = true;
        } else {
            // Force re-login after password change.
            AuthProfiles::logout();
            $this->view->password_changed = true;
        }
    }

    /**
     * Handle request to recover from a forgotten password.
     */
    public function forgotpassword() 
    {
        $form_data = $this->validate_form(
            $this->logins_model, 
            'validate_forgot_password', 'form_errors_auth'
        );
        if (null===$form_data) return;

        if (!empty($form_data['login_name'])) {
            $login = $this->logins_model
                ->fetch_by_login_name($form_data['login_name']);
        } elseif (!empty($form_data['email'])) {
            $login = $this->logins_model
                ->fetch_by_email($form_data['email']);
        }

        $reset_token = $this->logins_model
            ->set_password_reset_token($login['id']);
        $this->view->password_reset_token_set = true;

        email::send_view(
            $login['email'],
            'auth_profiles/forgotpassword_email',
            array(
                'password_reset_token' => $reset_token,
                'login_name' => $login['login_name']
            )
        );
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
                            'url' => "profiles/{$u_name}/settings/basics/changepassword",
                            'title' => 'Change login password',
                            'description' => 'change current login password'
                        ),
                        array(
                            'url' => "profiles/{$u_name}/settings/basics/changeemail",
                            'title' => 'Change login email',
                            'description' => 'change current login email'
                        ),
                        array(
                            'url' => "profiles/{$u_name}/settings/basics/details",
                            'title' => 'Edit profile details',
                            'description' => 'change screen name, bio, etc.'
                        ),
                        /*
                        array(
                            'url' => "profiles/{$u_name}/settings/basics/logins",
                            'title' => 'Manage profile logins',
                            'description' => 'create and remove logins for this profile'
                        ),
                         */
                        /*
                        array(
                            'url' => "profiles/{$u_name}/settings/basics/delete",
                            'title' => 'Delete profile',
                            'description' => 'delete this profile altogether'
                        ),
                         */
                    )
                )
            )
        );
        Event::run('auth_profiles.before_settings_menu', $data);
        $this->view->sections = $data['sections'];
    }
} 
