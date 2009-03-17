<?php
/**
 *
 *
 * @package    PHPUnit
 * @subpackage hooks
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class TestConfig 
{

    public static function init()
    {
        Event::add('EnvConfig.select_environment', 
            array('TestConfig', 'selectEnvironment'));
    }

    public static function selectEnvironment()
    {
        $env = isset($_SERVER['HTTP_X_USE_ENV']) ?
            $_SERVER['HTTP_X_USE_ENV'] : 'production';
        if ('testing' == $env) {
            Event::$data = 'testing';
        }
    }

}
TestConfig::init();
