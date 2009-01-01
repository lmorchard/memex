<?php
/**
 * Script for creating and loading database
 */

// use bootstrap (contains prepared db adapter and prepared table 
// component)
define('BOOTSTRAP', true);
//define('APPLICATION_ENVIRONMENT', 'production');
include_once dirname(__FILE__) . '/../application/bootstrap.php';

// if any parameter is passed after the script name (like 1 or --withdata)
// load the data file after the schema has loaded.
$withData = isset($_SERVER['argv'][1]);

// pull the adapter out of the application registry
$dbAdapter = Zend_Registry::get('db');

// let the user know whats going on (we are actually creating a 
// database here)
echo 'Writing Database Guestbook in (control-c to cancel): ' . PHP_EOL;

// this block executes the actual statements that were loaded from 
// the schema file.
try {
    $schemaSql = file_get_contents('./schema.sqlite.sql');
    // $schemaSql = file_get_contents('./schema.mysql.sql');

    $schemaSql_parts = explode(';', $schemaSql);
    foreach ($schemaSql_parts as $part) {
        $part = trim($part);
        if (!$part) continue;
        $dbAdapter->getConnection()->exec($part.';');
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

        $login_id = $logins_model->registerWithProfile(array(
            'login_name'  => 'deusx',
            'email'       => 'l.m.orchard@pobox.com',
            'password'    => 'Fey23Bork!',
            'screen_name' => 'deusx',
            'full_name'   => 'l.m.orchard',
            'bio'         => 'deusx from delicious.com'
        ));
        $profile = $logins_model->fetchDefaultProfileForLogin($login_id);
        $profile_id = $profile['id'];
        
        $data_xml = file_get_contents('./deusx.xml');
        $posts = simplexml_load_string($data_xml);
        $cnt = 0;
        foreach ($posts as $post) {
            $post_data = array(
                'profile_id' => $profile_id,
                'url'        => $post['href'],
                'title'      => $post['description'],
                'notes'      => $post['extended'],
                'tags'       => $post['tag'],
                'user_date'  => $post['time']
            );
            $posts_model->save($post_data);
            
            $cnt++;
            if ( ($cnt % 100) == 0)
               echo "$cnt...";
            else 
                echo '.';

            // if ($cnt > 200) break;
        }
        echo "\n";
        /*
        $dataSql = file_get_contents('./data.sqlite.sql');
        // use the connection directly to load sql in batches
        $dbAdapter->getConnection()->exec($dataSql);
        echo 'Data Loaded.';
        echo PHP_EOL;
         */
    }
    
} catch (Exception $e) {
    echo 'AN ERROR HAS OCCURED:' . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    return false;
}

// generally speaking, this script will be run from the command line
return true;
