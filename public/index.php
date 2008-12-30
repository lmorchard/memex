<?php
// define('APPLICATION_ENVIRONMENT', 'development');
define('APPLICATION_ENVIRONMENT', 'development_mysql');
// define('APPLICATION_ENVIRONMENT', 'production');

try {
    require '../application/bootstrap.php';
} catch (Exception $exception) {
    echo '<html><body><center>'
       . 'An exception occured while bootstrapping the application.';
    if (defined('APPLICATION_ENVIRONMENT') && APPLICATION_ENVIRONMENT != 'production') {
        echo '<br /><br />' . $exception->getMessage() . '<br />'
           . '<div align="left">Stack Trace:' 
           . '<pre>' . $exception->getTraceAsString() . '</pre></div>';
    }
    echo '</center></body></html>';
    exit(1);
}

Zend_Controller_Front::getInstance()->dispatch();
