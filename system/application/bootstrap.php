<?php
// Set up default app constants.
defined('APPLICATION_ENVIRONMENT')
    or define('APPLICATION_ENVIRONMENT', 'development');
defined('APPLICATION_PATH')
    or define('APPLICATION_PATH', dirname(__FILE__));

// Assemble some app-specific library paths.
set_include_path(join(PATH_SEPARATOR, array(
    APPLICATION_PATH . '/../library',
    APPLICATION_PATH . '/library', 
    APPLICATION_PATH . '/vendor',
    get_include_path()
)));

// Wire up the class autoloader.
require_once "Zend/Loader.php";
Zend_Loader::registerAutoload();

// Fire up the initialization plugin with the front controller.
require_once APPLICATION_PATH . '/library/Memex/Initialize.php';
$init = new Memex_Initialize(APPLICATION_ENVIRONMENT, APPLICATION_PATH);
$init->init();
