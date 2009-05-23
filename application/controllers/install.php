<?php
/**
 * Installation controller.
 *
 * @package    Memex
 * @subpackage controllers
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Install_Controller extends Local_Controller
{
    protected $auto_render = TRUE;

    /**
     * Set up the installation controller, set the layout
     */
    public function __construct()
    {
        parent::__construct();

        // Switch over to the installation layout
        $this->layout->set_filename('layout-install');

        // Make an educated guess about the location of the app before 
        // installation.
        Kohana::config_set('core.site_domain', 
            str_replace('index.php', '', $_SERVER['SCRIPT_NAME']));
    }

    /**
     * Index / welcome page for Memex installation.
     */
    public function index()
    {
        form::$defaults = array(
            'host'           => '127.0.0.1',
            'port'           => '3306',
            'database'       => 'memex_git',
            'username'       => 'memex',
            'password'       => 'memex',
            'admin_username' => 'root',
            'admin_password' => '',
            'site_title'     => 'Memex',
            'base_url'       => Kohana::config('core.site_domain')
        );

        if ('post' !== request::method())
            return;

        $valid = Validation::factory($this->input->post())
            ->pre_filter('trim');
        foreach (form::$defaults as $name=>$default)
            if (!empty($default)) $valid->add_rules($name, 'required');

        if (!$valid->validate()) {
            form::$data = $valid->as_array();
            $this->view->set('errors', 
                $valid->errors('install_errors'));
            return;
        }

        if (!$this->_setupDatabase($valid)) {
            $this->view->set('errors', 
                $valid->errors('install_errors'));
            return;
        }

        list($config_fn, $config_src) = $this->_writeConfigFile($valid);
        if (false === $config_fn) {
            // Config writing was a success, so yay.
            url::redirect('install/step2');
        } else {
            $this->view->set(array(
                'config_not_writable' => true,
                'config_fn' => $config_fn,
                'config_src' => $config_src
            ));
        }
    }

    /**
     * Try setting up the database for the application
     * The validation object will be updated with errors if any occur.
     *
     * @param Validation
     * @return boolean whether the process was successful.
     */
    public function _setupDatabase($valid)
    {
        try {
            // First, take over error handling for database operations.
            // Any exceptions will be caught here.
            $eh = set_error_handler(create_function('','return;'));

            // Try logging in as the admin user.
            $db = new Database(array(
                'type'     => 'mysql',
                'user'     => $valid->admin_username,
                'pass'     => $valid->admin_password,
                'host'     => $valid->host,
                'port'     => $valid->port,
                'database' => 'information_schema',
                'socket'   => FALSE
            ));

            // Drop the database (just in case), create it, and use it.
            $db->query("DROP DATABASE IF EXISTS {$valid->database}");
            $db->query("CREATE DATABASE {$valid->database}");
            $db->query("USE {$valid->database}");

            // Read in the schema and run each of the statements.
            $schema_data = file_get_contents(APPPATH . 'schema/mysql.sql');
            $stmts = array_filter(explode(';', $schema_data), 'trim');
            foreach ($stmts as $stmt) {
                if (empty($stmt)) continue;
                $db->query($stmt);
            }

            set_error_handler($eh);
        } catch (Kohana_Database_Exception $e) {
            set_error_handler($eh);

            // Something went wrong, so flag the admin login as invalid.
            $valid
                ->add_error('admin_password', 'invalid')
                ->add_error('admin_username', 'invalid');

            $msg = $e->getMessage();
            if (strpos($msg, 'error connecting')) {
                // Problem was no connection allowed for user.
                $valid->add_error('admin_username', 'no_connection');
                return false;
            } else if (strpos($msg, 'Access denied for user')) {
                // Problem was the valid user was denied access.
                $valid->add_error('admin_username', 'no_access');
                return false;
            }

            // Throw up on something unexpected.
            throw $e; 
        }

        // HACK: Try logging in with application DB user and note whether there 
        // was an error.
        try {
            $eh = set_error_handler(create_function('','return;'));
            $user_db = new Database(array(
                'type'     => 'mysql',
                'user'     => $valid->username,
                'pass'     => $valid->password,
                'host'     => $valid->host,
                'port'     => $valid->port,
                'database' => $valid->database,
                'socket'   => FALSE
            ));
            $user_db->connect();
            set_error_handler($eh);
            $login_success = TRUE;
        } catch (Kohana_Database_Exception $e) {
            set_error_handler($eh);
            $login_success = FALSE;
        }

        // If no successful login, try creating a user with access to the new 
        // database.
        if (!$login_success) {
            try {
                $eh = set_error_handler(create_function('','return;'));

                $db->query("
                    GRANT ALL PRIVILEGES ON {$valid->database}.*
                    TO {$valid->username}@{$valid->host}
                    IDENTIFIED BY '{$valid->password}'
                ");

                set_error_handler($eh);
            } catch (Kohana_Database_Exception $e) {
                set_error_handler($eh);
                // Note that the admin user couldn't grant access to this user.
                $valid
                    ->add_error('admin_username', 'no_grant')
                    ->add_error('admin_password', 'invalid')
                    ->add_error('admin_username', 'invalid');
                return false;
            }
        }

        return true;
    }

    /**
     * Try building and writing the config file.
     *
     * @param Validation
     * @return array config filename, config source - or both false if success.
     */
    public function _writeConfigFile($valid)
    {
        $config_data = array(
            'core.needs_installation' => false,
            'core.site_title' => $valid->site_title,
            'core.site_domain' => $valid->base_url,
            'model.database' => 'local',
            'database.local' => array(
                'benchmark'     => FALSE,
                'persistent'    => TRUE,
                'connection'    => array
                (
                    'type'     => 'mysql',
                    'user'     => $valid->username,
                    'pass'     => $valid->password,
                    'host'     => $valid->host,
                    'port'     => $valid->port,
                    'socket'   => FALSE,
                    'database' => $valid->database
                ),
                'character_set' => 'utf8',
                'table_prefix'  => '',
                'object'        => FALSE,
                'cache'         => FALSE,
                'escape'        => TRUE
            )
        );

        $config_src = '<?'.'php $config = ' . 
            var_export($config_data, true) . ';';

        $config_fn = APPPATH . 'config/config-local.php';

        if (!is_writable($config_fn)){
            return array($config_fn, $config_src);
        } else {
            file_put_contents($config_fn, $config_src);
            return array(false, false);
        }
    }

    /**
     *
     */
    public function step2()
    {

        echo "SUCCESS"; die;

    }

}
