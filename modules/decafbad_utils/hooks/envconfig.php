<?php
/**
 * Hook to override application configs via a selectable environmental config 
 * override.
 *
 * Other hooks can respond to the EnvConfig.select_environment event to supply 
 * a method for selecting the environment.
 *
 * In order to rely on the selected configs, other hooks must register handlers 
 * with Event::add('EnvConfig.ready')
 *
 * @TODO Load config overrides per hostname / environment
 *
 * @package    DecafbadUtils
 * @subpackage hooks
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class DecafbadUtils_EnvConfig {

    public static function init()
    {
        $env = 'local';
        Event::run('EnvConfig.select_environment', $env);
        self::apply(!empty($env) ? $env : 'local');
        Event::run('EnvConfig.ready', $env);
    }

    public static function apply($env)
    {
        $config = array();
        $fn = Kohana::find_file('config', 'config-' . $env);
        if (!empty($fn)) {
            foreach ($fn as $f) {
                include($f);
                if (isset($config) && !empty($config)) {
                    foreach ($config as $key => $value) {
                        Kohana::config_set($key, $value);
                    }
                }
            }
        }
    }

}
DecafbadUtils_EnvConfig::init();
