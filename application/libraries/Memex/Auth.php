<?php
/**
 * Authentication cookie manager.
 */
class Memex_Auth {

    protected $cookie_name = 'memex_auth';
    protected static $instance = NULL;

    /**
     * Get a singleton instance of this class.
     *
     * @return Memex_Auth
     */
    public static function getInstance()
    {
        if (null == self::$instance)
            self::$instance = new self();
        return self::$instance;
    }

    /**
     * Construct the auth object.
     */
    public function __construct()
    {
        $this->cm = new BigOrNot_CookieManager(
            Kohana::config('auth.secret'),
            array(
            )
        );
        if (Kohana::config('auth.cookie_name', FALSE, FALSE))
            $this->cookie_name = Kohana::config('auth.cookie_name');
    }

    /**
     * Create a new authentication cookie.
     *
     * @param string user name
     * @param mixed data associated with logged in user
     */
    public function login($user_name, $user_data)
    {
        $this->cm->setCookie(
            $this->cookie_name, 
            serialize($user_data),
            $user_name
        );
    }

    /**
     * Destroy the current authenticated login.
     */
    public function logout()
    {
        $this->cm->deleteCookie($this->cookie_name);
    }

    /**
     * Determine whether there's a valid existing authenticated login.
     *
     * @return boolean
     */
    public function isLoggedIn()
    {
        $cv = $this->cm->getCookieValue($this->cookie_name);
        return !empty( $cv );
    }

    /**
     * Return the data for the currently logged in user, if any.
     *
     * @return mixed
     */
    public function getUserData()
    {
        $data = $this->cm->getCookieValue($this->cookie_name);
        return $data ? unserialize($data) : null;
    }

}
