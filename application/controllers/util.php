<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Collection of mostly command line utilities.
 *
 * @package Memex
 * @author  l.m.orchard <l.m.orchard@pobox.com>
 * @license http://creativecommons.org/licenses/BSD/
 */
class Util_Controller extends Controller {

    function __construct()
    {
        error_reporting( E_ALL | E_STRICT );
        restore_exception_handler();
        restore_error_handler();
        ob_end_clean();
        Kohana::config_set('model.enable_delete_all', true);
    }

	function loadxml()
	{
        if ('cli' !== PHP_SAPI)
            die("For command-line use only.");

        // Turn off logging for now because flushing the log takes forever at 
        // the end of the import.
        Kohana::config_set('core.log_threshold', 0);

        $args   = $_SERVER['argv'];
        $script = array_shift($args);
        $route  = array_shift($args);

        $xml_fn = array_shift($args);
        $posts  = simplexml_load_file($xml_fn);

        $user_name = (string)$posts['user'];

        $logins_model   = new Logins_Model();
        $profiles_model = new Profiles_Model();
        $posts_model    = new Posts_Model();

        $login = $logins_model->fetchByLoginName($user_name);
        if (!empty($login)) {
            // Delete existing posts if login found.
            // HACK: Make this a command-line switch?
            // $posts_model->deleteAll();
            echo "Pre-existing account for '$user_name'\n";
        } else {
            // Create a new login from the user name.
            echo "Registering account for '$user_name'\n";
            $login = $logins_model->registerWithProfile(array(
                'login_name'  => $user_name,
                'email'       => 'l.m.orchard@pobox.com',
                'password'    => 'password',
                'screen_name' => 'deusx',
                'full_name'   => 'l.m.orchard',
                'bio'         => ''
            ));
        }

        $profile = $logins_model->fetchDefaultProfileForLogin($login['id']);
        $profile_id = $profile['id'];
        
        $total = count($posts->post);
        echo "Importing " . $total . " posts...\n";
        $cnt = 0;
        foreach ($posts as $post) {
            $post_data = array(
                'profile_id' => $profile_id,
                'url'        => (string)$post['href'],
                'title'      => (string)$post['description'],
                'notes'      => (string)$post['extended'],
                'tags'       => (string)$post['tag'],
                'user_date'  => (string)$post['time']
            );
            $posts_model->save($post_data);
            
            if ( (++$cnt % 100) == 0) {
               echo "\n$cnt (" . (int)(($cnt/$total)*100) . "%)";
            } else {
               echo '.';
            }

            // if ($cnt > 300) break;
        }
        echo "\nDone!\n";
	}

}
