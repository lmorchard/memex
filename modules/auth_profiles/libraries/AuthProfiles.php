<?php
/**
 * Main auth profiles helper
 *
 * @package    auth_profiles
 * @subpackage libraries
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class AuthProfiles
{
    public static $cookie_manager = null;
    public static $cookie_name = 'auth_profiles';

    /**
     * Iniitalize the helper.
     */
    public static function init()
    {
        require_once Kohana::find_file('vendor', 'BigOrNot/CookieManager');
        self::$cookie_manager = new BigOrNot_CookieManager(
            Kohana::config('auth_profiles.secret'),
            array()
        );
        if (Kohana::config('auth_profiles.cookie_name', FALSE, FALSE))
            self::$cookie_name = Kohana::config('auth_profiles.cookie_name');
    }

    /**
     * Create a new authentication cookie.
     *
     * @param string user name
     * @param mixed data associated with logged in user
     */
    public static function login($user_name, $login, $profile)
    {
        $user_data = array(
            'login' => $login, 
            'profile' => $profile
        );
        $duration = Kohana::config('auth_profiles.login_duration');
        if (empty($duration)) 
            $duration = ( 52 * 7 * 24 * 60 * 60 );
        self::$cookie_manager->setCookie(
            self::$cookie_name, 
            serialize($user_data),
            $user_name,
            time() + $duration
        );
    }

    /**
     * Destroy the current authenticated login.
     */
    public static function logout()
    {
        self::$cookie_manager->deleteCookie(self::$cookie_name);
    }

    /**
     * Determine whether there's a valid existing authenticated login.
     *
     * @return boolean
     */
    public static function is_logged_in()
    {
        $cv = self::$cookie_manager->getCookieValue(self::$cookie_name);
        return !empty( $cv );
    }

    /**
     * Return the data for the currently logged in user, if any.
     *
     * @return mixed
     */
    public static function get_user_data()
    {
        $data = self::$cookie_manager->getCookieValue(self::$cookie_name);
        return $data ? unserialize($data) : null;
    }

    public static function get_login($key=null, $default=null)
    {
        $user_data = self::get_user_data();
        if (null===$key) {
            return $user_data['login'];
        } else {
            return isset($user_data['login'][$key]) ?
                $user_data['login'][$key] : $default;
        }
    }

    public static function get_profile($key=null, $default=null)
    {
        $user_data = self::get_user_data();
        if (null===$key) {
            return $user_data['profile'];
        } else {
            return isset($user_data['profile'][$key]) ?
                $user_data['profile'][$key] : $default;
        }
    }

}
