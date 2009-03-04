<?php
/**
 * Hook to override application configs via a config-local.php file.
 *
 * @TODO: Load config overrides per hostname / environment
 */
class LocalConfig {
    public static function init()
    {
        $config = array();
        require APPPATH.'config/config-local'.EXT;
        if (isset($config) && !empty($config)) {
            foreach ($config as $key => $value) {
                Kohana::config_set($key, $value);
            }
        }
    }
}
LocalConfig::init();
