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
        return url::redirect('login');
    }

    /**
     * Convenience action to redirect to logged in user's default profile.
     */
    public function home()
    {
        if (!$this->auth->isLoggedIn()) {
            url::redirect('login');
        } else {
            url::redirect('profile/' . 
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

        $logins = new Logins_Model();
        $validator = $logins->getRegistrationValidator(
            $this->input->post()
        );

        if (!$validator->validate()) {
            $this->setViewData(
                'errors', $validator->errors('form_errors_auth')
            );
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
        if ('post' != request::method())
            return;

        $logins = new Logins_Model();
        $validator = $logins->getLoginValidator(
            $this->input->post()
        );

        if (!$validator->validate()) {
            $this->setViewData(
                'errors', $validator->errors('form_errors_auth')
            );
            return;
        }

        $data = $validator->as_array();

        $login   = $logins->fetchByLoginName($data['login_name']);
        $profile = $logins->fetchDefaultProfileForLogin($login['id']);

        $this->auth->login($data['login_name'], array(
            'login' => $login, 'profile' => $profile
        ));

        if (isset($data['jump']) && substr($data['jump'], 0, 1) == '/') {
            // Allow post-login redirect only if the param starts with '/', 
            // interpreted as relatve to root of site.
            return url::redirect($data['jump']);
        } else {
            return url::redirect();
        }
    }

    /**
     * User logout action.
     */
    public function logout()
    {
        $this->setViewData(array(
            'auth_login'   => null,
            'auth_profile' => null
        ));
        $this->auth->logout();
    }
         
} 
