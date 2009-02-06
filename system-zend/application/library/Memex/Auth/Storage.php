<?php
/**
 * A non-session cookie storage handler for auth
 *
 * see: http://bigornot.blogspot.com/2008/06/securing-cookies-php-implementation.html
 *
 * @package Memex
 * @author l.m.orchard <l.m.orchard@pobox.com>
 */
class Memex_Auth_Storage implements Zend_Auth_Storage_Interface
{

    /** Secret key for the secure storage protocol */
    protected $secret;
    /** Name for the auth cookie */
    protected $cookie_name;
    /** Instance of BigOrNot_CookieManager backing this storage mechanism */
    protected $manager;
    /** Login name used for cookie encryption */
    protected $user_name;

    /**
     * Construct the auth storage
     */
    public function __construct($secret, $user_name='')
    {
        $this->secret      = $secret;
        $this->cookie_name = 'Memex-'.APPLICATION_ENVIRONMENT;
        $this->manager     = new BigOrNot_CookieManager($this->secret);
        $this->user_name   = $user_name;
    }

    /**
     * Set the user name used in setting the cookie.
     */
    public function setUserName($user_name)
    {
        $this->user_name = $user_name;
    }

    /**
     * Returns true if and only if storage is empty
     *
     * @throws Zend_Auth_Storage_Exception
     * @return boolean
     */
    public function isEmpty()
    {
        return !$this->manager->cookieExists($this->cookie_name);
    }

    /**
     * Returns the contents of storage
     *
     * Behavior is undefined when storage is empty.
     *
     * @throws Zend_Auth_Storage_Exception
     * @return mixed
     */
    public function read()
    {
        $data = $this->manager->getCookieValue($this->cookie_name);
        return unserialize($data);
    }

    /**
     * Writes $contents to storage
     *
     * @param  mixed $contents
     * @throws Zend_Auth_Storage_Exception
     * @return void
     */
    public function write($contents)
    {
        $this->manager->setCookie(
            $this->cookie_name,
            serialize($contents),
            $this->user_name,
            time() + ( 60 * 60 * 24 * 7 * 8 )
        );

        $this->manager->setCookie(
            '_user',
            serialize($contents),
            $this->user_name,
            time() + ( 60 * 60 * 24 * 7 * 8 )
        );

    }

    /**
     * Clears contents from storage
     *
     * @throws Zend_Auth_Storage_Exception
     * @return void
     */
    public function clear()
    {
        return $this->manager->deleteCookie($this->cookie_name);
    }

}
