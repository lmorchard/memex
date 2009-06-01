<?php
/**
 * Application-local controller customizations.
 *
 * @package    Memex
 * @subpackage controllers
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Local_Controller extends Controller 
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

    }

}
