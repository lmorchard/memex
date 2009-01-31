<?php
// define('APPLICATION_ENVIRONMENT', 'production');
define('APPLICATION_ENVIRONMENT', 'development_mysql');
require dirname(dirname(__FILE__)).'/application/bootstrap.php';
$worker = new Memex_MessageQueueWorker();
$worker->run();
