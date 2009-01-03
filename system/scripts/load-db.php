<?php
/**
 * Script for creating and loading database
 */

// use bootstrap (contains prepared db adapter and prepared table 
// component)
define('BOOTSTRAP', true);
define('APPLICATION_ENVIRONMENT', 'development_mysql');
include_once dirname(__FILE__) . '/../application/bootstrap.php';

$config = Zend_Registry::get('config');
$config->batch_mode = true;

// if any parameter is passed after the script name (like 1 or --withdata)
// load the data file after the schema has loaded.
$withData = isset($_SERVER['argv'][1]);

// pull the adapter out of the application registry
$dbAdapter = Zend_Registry::get('db');

// let the user know whats going on (we are actually creating a 
// database here)
$schema_fn = $config->database->schema;
echo "Creating DB from schema $schema_fn\n";

/*
 * Setup default DB adapter
 */
$db = Zend_Db_Table_Abstract::getDefaultAdapter();
$schema_sql = file_get_contents(APPLICATION_PATH.'/schema/'.$schema_fn);

// Break the schema SQL up into separate statements, to prevent a buffered 
// query issue qith MySQL
$schema_sql_parts = explode(';', $schema_sql);
foreach ($schema_sql_parts as $part) {
    $part = trim($part);
    if (!$part) continue;
    try {
        $db->query($part.';');
    } catch (Exception $e) {
        echo "$part\n";
        throw $e;
    }
}

echo PHP_EOL;
echo 'Database Created';
echo PHP_EOL;

if ($withData) {
    require_once APPLICATION_PATH . '/models/Logins.php';
    $logins_model = new Memex_Model_Logins();

    require_once APPLICATION_PATH . '/models/Profiles.php';
    $profiles_model = new Memex_Model_Profiles();

    require_once APPLICATION_PATH . '/models/Posts.php';
    $posts_model = new Memex_Model_Posts();

    $login = $logins_model->registerWithProfile(array(
        'login_name'  => 'deusx',
        'email'       => 'l.m.orchard@pobox.com',
        'password'    => 'Testing123!',
        'screen_name' => 'deusx',
        'full_name'   => 'l.m.orchard',
        'bio'         => 'deusx from delicious.com'
    ));

    $profile = $logins_model->fetchDefaultProfileForLogin($login['id']);
    $profile_id = $profile['id'];
    
    $data_xml = file_get_contents('./deusx.xml');
    $posts = simplexml_load_string($data_xml);
    $cnt = 0;
    foreach ($posts as $post) {
        try{

            $post_data = array(
                'profile_id' => $profile_id,
                'url'        => (string)$post['href'],
                'title'      => (string)$post['description'],
                'notes'      => (string)$post['extended'],
                'tags'       => (string)$post['tag'],
                'user_date'  => (string)$post['time']
            );
            $posts_model->save($post_data);
            
            $cnt++;
            if ( ($cnt % 100) == 0)
               echo "$cnt...";
            else 
                echo '.';

        } catch (Exception $e) {
            var_export($post_data);
            echo "exception '".get_class($e)."' with message '".$e->getMessage().
                "'\nStack trace:\n".$e->getTraceAsString();
            die;
        }
        // if ($cnt > 200) break;
    }
    echo "\n";
}

// generally speaking, this script will be run from the command line
return true;
