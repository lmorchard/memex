<?php
/**
 * Hook to override application configs via a config-local.php file.
 *
 * In order to rely on the local configs, other hooks must register handlers 
 * with Event::add('LocalConfig.ready')
 *
 * @TODO: Load config overrides per hostname / environment
 */
class LocalConfig {
    public static function init()
    {
        $config = array();
        require APPPATH.'config/config-local'.EXT;
        Event::run('LocalConfig.loaded', $config);
        if (isset($config) && !empty($config)) {
            foreach ($config as $key => $value) {
                Kohana::config_set($key, $value);
            }
        }
        Event::run('LocalConfig.ready', $config);
    }
}
LocalConfig::init();
