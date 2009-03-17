<?php
// define('APPLICATION_ENVIRONMENT', 'production');
define('APPLICATION_ENVIRONMENT', 'development_mysql');
require dirname(dirname(__FILE__)).'/application/bootstrap.php';
Zend_Registry::get('message_queue')->run();
