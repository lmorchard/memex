<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * PHPUnit test integration
 *
 * @package Memex
 * @author  l.m.orchard <l.m.orchard@pobox.com>
 */
class Tests_Controller extends Controller {

    function __construct()
    {
        error_reporting( E_ALL | E_STRICT );
        restore_exception_handler();
        restore_error_handler();
        ob_end_clean();
        Kohana::config_set('model.enable_delete_all', true);
    }

	function index()
	{
        if ('cli' !== PHP_SAPI)
            die("For command-line use only.");

        chdir(APPPATH . '/tests');
        array_splice($_SERVER['argv'], 0, 2, array('phpunit', '--configuration', 'phpunit.xml'));
        require_once 'PHPUnit/Util/Filter.php';
        PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');
        require 'PHPUnit/TextUI/Command.php';
        echo Kohana::lang('core.stats_footer')."\n";

	}

}
