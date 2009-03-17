<?php
/**
 * Application-local controller customizations.
 *
 * @package    Memex
 * @subpackage controllers
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Local_Controller extends Layout_Controller 
{
    protected $auto_render = TRUE;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // Start with empty set of view vars.
        $this->view->set_global(array(
            'form_data' => array(),
            'form_errors' => array(),
            'profile_home_url' => '',
            'screen_name' => ''
        ));

        $this->auth = Memex_Auth::getInstance();

        if ($this->auth->isLoggedIn()) {
            $this->auth_data = $auth_data = $this->auth->getUserData();
            $this->view->set_global(array(
                'auth_login'   => $auth_data['login'],
                'auth_profile' => $auth_data['profile']
            ));
        } else {
            $this->auth_data = null;
            $this->view->set_global(array(
                'auth_login'   => null,
                'auth_profile' => null
            ));
        }

    }

}
