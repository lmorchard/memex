<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 *
 * @package Memex
 * @author  l.m.orchard <l.m.orchard@pobox.com>
 * @license http://creativecommons.org/licenses/BSD/
 */
class Tests_Controller extends Controller {

	function index()
	{
        // var_export( Kohana::include_paths() ); die; 

        error_reporting( E_ALL | E_STRICT );
        restore_exception_handler();
        restore_error_handler();
        Kohana::config_set('model.enable_delete_all', true);

        $path = array(
            APPPATH,
            get_include_path()
        );
        set_include_path(implode(PATH_SEPARATOR, $path));

        if (PHP_SAPI === 'cli') {
            chdir(APPPATH . '/tests');
            array_splice($_SERVER['argv'], 0, 2, array('phpunit', '--configuration', 'phpunit.xml'));
            require_once 'PHPUnit/Util/Filter.php';
            PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');
            require 'PHPUnit/TextUI/Command.php';
            echo Kohana::lang('core.stats_footer')."\n";
        }

	}

}
