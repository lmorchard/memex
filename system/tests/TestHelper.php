<?php
/*
 * Start output buffering
 */
ob_start();
error_reporting( E_ALL | E_STRICT );
date_default_timezone_set('GMT');

/*
 * Determine the root, library, tests, and models directories
 */
$root        = realpath(dirname(__FILE__) . '/../');
$library     = $root . '/library';
$tests       = $root . '/tests';
$app_library = $root . '/application/library';
$models      = $root . '/application/models';
$controllers = $root . '/application/controllers';

define('APPLICATION_ENVIRONMENT', 'testing_sqlite');
define('APPLICATION_PATH', $root.'/application');

include_once $root . '/application/bootstrap.php';

/*
 * Prepend the library/, tests/, and models/ directories to the
 * include_path. This allows the tests to run out of the box.
 */
$path = array(
    $models,
    $library,
    $app_library,
    $tests,
    get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $path));

/*
 * Add library/ and models/ directory to the PHPUnit code coverage
 * whitelist. This has the effect that only production code source files appear
 * in the code coverage report and that all production code source files, even
 * those that are not covered by a test yet, are processed.
 */
if (defined('TESTS_GENERATE_REPORT') && TESTS_GENERATE_REPORT === true &&
    version_compare(PHPUnit_Runner_Version::id(), '3.1.6', '>=')) {
    PHPUnit_Util_Filter::addDirectoryToWhitelist($library);
    PHPUnit_Util_Filter::addDirectoryToWhitelist($app_library);
    PHPUnit_Util_Filter::addDirectoryToWhitelist($models);
    PHPUnit_Util_Filter::addDirectoryToWhitelist($controllers);
}

/*
 * Setup default DB adapter
 */
$db = Zend_Db_Table_Abstract::getDefaultAdapter();
$schema_sql = file_get_contents($root.'/scripts/schema.sqlite.sql');
$db->getConnection()->exec($schema_sql);

/*
 * Unset global variables that are no longer needed.
 */
unset($init, $root, $library, $models, $controllers, $tests, $path, $db);
