<?php
/**
 * Collection of mostly command line utilities.
 *
 * @package    Memex
 * @subpackage controllers
 * @author     l.m.orchard <l.m.orchard@pobox.com>
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

        Kohana::config_set('messagequeue.deferred_events', FALSE);
        Kohana::config_set('model.batch_mode', TRUE);

        $args   = $_SERVER['argv'];
        $script = array_shift($args);
        $route  = array_shift($args);

        $xml_fn = array_shift($args);
        $posts  = simplexml_load_file($xml_fn);

        $user_name = (string)$posts['user'];

        $login_model = new Login_Model();
        $posts_model = new Posts_Model();

        $login = ORM::factory('login', $user_name);
        if ($login->loaded) {
            // Delete existing posts if login found.
            // HACK: Make this a command-line switch?
            // $posts_model->deleteAll();
            echo "Pre-existing account for '$user_name'\n";
        } else {
            // Create a new login from the user name.
            echo "Registering account for '$user_name'\n";
            $login_data = $login_model->register_with_profile(array(
                'login_name'  => $user_name,
                'email'       => "{$user_name}@memex",
                'password'    => 'password',
                'screen_name' => $user_name,
                'full_name'   => $user_name,
                'bio'         => ''
            ), true);
            $login = ORM::factory('login', $login_data->id);
        }

        $profile = $login->find_default_profile_for_login();
        $profile_id = $profile->id;
        
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
