<?php
/**
 * Application initialization plugin.
 */
class Memex_Initialize
{
    /**
     * @var Zend_Config
     */
    protected static $_config;

    /**
     * @var Zend_Registry 
     */
    protected $_registry;

    /**
     * @var Zend_Log 
     */
    protected $_logger;

    /**
     * @var string Current environment
     */
    protected $_env;

    /**
     * @var Zend_Controller_Front
     */
    protected $_front;

    /**
     * @var string Path to application root
     */
    protected $_appPath;

    /**
     * Constructor
     *
     * Initialize environment, application path, and configuration.
     * 
     * @param  string $env 
     * @param  string|null $appPath
     * @return void
     */
    public function __construct($env, $appPath = null)
    {
        $this->_setEnv($env);
        if (null === $appPath) {
            $appPath = realpath(dirname(__FILE__) . '/../');
        }
        $this->_appPath  = $appPath;
        $this->_front    = Zend_Controller_Front::getInstance();
        $this->_registry = Zend_Registry::getInstance();

        Zend_Locale::$compatibilityMode = false;
    }

    /**
     * Route startup
     * 
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        $this->init();
    }
     */

    /**
     * Perform app initialization.
     */
    public function init() 
    {
        // $this->initPathCache()
        $this->initLogger()
             ->initDb()
             ->initMessaging()
             ->initHelpers()
             ->initView()
             ->initPlugins()
             ->initRoutes()
             ->initControllers();
        return $this;
    }

    /**
     * Get config object (static)
     * 
     * @return Zend_Config
     */
    public static function getConfig()
    {
        return self::$_config;
    }

    /**
     * Initialize the file map cache for Zend_Loader
     * 
     * @return Memex_Plugin_Initialize
     */
    public function initPathCache()
    {
        $pluginIncFile = $this->_appPath . '/../data/cache/plugins.inc.php';
        if (file_exists($pluginIncFile)) {
            include_once $pluginIncFile;
        }
        Zend_Loader::setIncludeFileCache($pluginIncFile);
        Zend_Loader_PluginLoader::setIncludeFileCache($pluginIncFile);
        return $this;
    }

    /**
     * Initialize logger
     * 
     * @return Memex_Plugin_Initialize
     */
    public function initLogger()
    {
        $config = $this->_getConfig();

        // Set up logging from configuration.
        if (empty($config->log->writer)) {
            $writer = new Zend_Log_Writer_Null;
        } else {
            switch ($config->log->writer) {
                case 'Firebug':
                    $writer = new Zend_Log_Writer_Firebug();
                    break;
                default:
                    $writer = new Zend_Log_Writer_Stream(
                        $config->log->path
                    );
                    break;
            }
        }
        $logger = new Zend_Log($writer);
        
        $filter = new Zend_Log_Filter_Priority(
            empty($config->log->priority) ? 
                Zend_Log::CRIT : (int)$config->log->priority
        );
        $logger->addFilter($filter);

        Zend_Registry::set('logger', $logger);
        return $this;
    }

    /**
     * Initialize DB
     * 
     * @return Memex_Plugin_Initialize
     */
    public function initDb()
    {
        $config = $this->_getConfig();

        $db = Zend_Db::factory($config->database);
        if ($config->database->profile) {
            $db->getProfiler()->setEnabled(TRUE);
        }

        Zend_Db_Table_Abstract::setDefaultAdapter($db);
        Zend_Registry::set('db', $db);
        return $this;
    }

    /**
     * Initialize messaging and subscriptions
     */
    public function initMessaging()
    {
        $config = $this->_getConfig();

        $nc = Memex_NotificationCenter::getInstance();

        $subs = array(

            array(Memex_Constants::TOPIC_POST_UPDATED, 'Memex_Model_Tags', 'handlePostUpdated'),
            array(Memex_Constants::TOPIC_POST_DELETED, 'Memex_Model_Tags', 'handlePostDeleted'),

            array(Memex_Constants::TOPIC_POST_UPDATED, 'Memex_Plugin_Delicious', 'handlePostUpdated'),
            array(Memex_Constants::TOPIC_POST_DELETED, 'Memex_Plugin_Delicious', 'handlePostDeleted'),

        );
        foreach ($subs as $sub) {
            list($topic, $class, $method) = $sub;
            $nc->subscribe($topic, $class, $method);
        }

        Zend_Registry::set('notification_center', $nc);
        return $this;
    }

    /**
     * Initialize action helpers
     * 
     * @return Memex_Plugin_Initialize
     */
    public function initHelpers()
    {
        Zend_Controller_Action_HelperBroker::addPath(
            $this->_appPath . '/controllers/helpers', 'Memex_Helper'
        );
        return $this;
    }

    /**
     * Initialize view and layout
     * 
     * @return Memex_Plugin_Initialize
     */
    public function initView()
    {
        $config = $this->_getConfig();

        $view = new Zend_View();

        // Set default base path for view.
        $view->addBasePath($this->_appPath . '/views/base', 'Memex_View_');

        if ($config->view->theme) {
            // Set next path for resource search as the theme named in config.
            $view->addBasePath($this->_appPath . '/views/' . $config->view->theme, 'Memex_View_');
        }

        // Set view in ViewRenderer
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

        // Initialize layouts
        Zend_Layout::startMvc();

        return $this;
    }

    /**
     * Initialize plugins
     * 
     * @return Memex_Plugin_Initialize
     */
    public function initPlugins()
    {
        $loader = new Zend_Loader_PluginLoader(array(
            'Memex_Plugin' => $this->_appPath . '/plugins/',
        ));
        $class = $loader->load('Auth');
        $this->_front->registerPlugin(new $class());
        return $this;
    }

    /**
     * Initialize routes
     * 
     * @return Memex_Plugin_Initialize
     */
    public function initRoutes()
    {
        $config = $this->_getConfig();
        $router = $this->_front->getRouter();

        $router->addConfig($config, 'routes');

        return $this;
    }

    /**
     * Initialize controller directories
     * 
     * @return Memex_Plugin_Initialize
     */
    public function initControllers()
    {
        $this->_front->addControllerDirectory($this->_appPath . '/controllers/');
        return $this;
    }

    /**
     * Get configuration object
     * 
     * @return Zend_Config
     */
    protected function _getConfig()
    {
        if (null === self::$_config) {

            $php_files = array(
                'routes.php'
            );
            $ini_files = array( 
                'app.ini' 
            );

            self::$_config = new Zend_Config(array(
                'root'        => $this->_appPath,
                'environment' => $this->_env
            ), true);
            
            foreach ($php_files as $fn) {
                self::$_config->merge(new Zend_Config(
                    require $this->_appPath . '/config/' . $fn
                ));
            }

            foreach ($ini_files as $fn) {
                self::$_config->merge(new Zend_Config_Ini(
                    $this->_appPath . '/config/' . $fn,
                    $this->_env,
                    true
                ));
            }

            $this->_registry->config = self::$_config;
        }
        return self::$_config;
    }

    /**
     * Set environment
     * 
     * @param  string $env 
     * @return void
     */
    protected function _setEnv($env)
    {
        /*
        if (!in_array($env, array('development', 'testing', 'production'))) {
            $env = 'development';
        }
        */
        $this->_env = $env;
    }
}
