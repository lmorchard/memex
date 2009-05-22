<?php
/**
 * Installation controller.
 *
 * @package    Memex
 * @subpackage controllers
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Install_Controller extends Local_Controller
{
    protected $auto_render = TRUE;

    /**
     * Set up the installation controller, set the layout
     */
    public function __construct()
    {
        parent::__construct();
        $this->layout->set_filename('layout-install');
    }

    /**
     *
     */
    public function index()
    {



    }

}
