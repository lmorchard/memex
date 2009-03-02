<?php 
/**
 * Methods to support profile settings.
 *
 * @author l.m.orchard <l.m.orchard@pobox.com>
 * @package Memex
 */
class Profile_Controller extends Controller  
{ 
    protected $auto_render = TRUE;

    public static $settings_content = array();

    public function __construct()
    {
        parent::__construct();
        if (!$this->auth->isLoggedIn()) {
            return url::redirect(
                url::base() . '/login' .
                '?jump=' . rawurlencode( url::current(TRUE) )
            );
        }
    }

    /**
     */
    public function index() 
    { 
    } 

    /**
     * Profiles settings.
     */
    public function settings()
    {
        $params = $this->getParamsFromRoute(array());

        if ($params['screen_name'] != $this->auth_data['profile']['screen_name']) {
            header("HTTP/1.1 403 Forbidden"); 
            exit;
        }

        // Set up initial whiteboard, fire off event to gather content from 
        // interested listeners.
        $data = array(
            'controller' => $this, 
            'auth_data'  => $this->auth_data,
            'sections'   => array()
        );
        Event::run('Memex.pre_settings_menu', $data);
        $this->setViewData('sections', $data['sections']);
    }

} 
