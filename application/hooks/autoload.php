<?php
/**
 * Autoloader class layered atop Kohana's default, allows organization of 
 * classes with an underscore-to-slash directory convention.
 */
class Memex_Autoloader
{
    public static function auto_load($class)
    {
        // Call Kohana's autoloader first.
        if ( Kohana::auto_load($class) )
            return TRUE;

        // If Kohana comes up empty, try replacing underscores with directory 
        // separators and look for a library.
        $file = str_replace('_', '/', $class);
		if ($filename = Kohana::find_file('libraries', $file)) {
            require $filename; return TRUE;
        } else if ($filename = Kohana::find_file('vendor', $file)) {
            require $filename; return TRUE;
        } else {
            return FALSE;
        }
    }
}

spl_autoload_register(array('Memex_Autoloader', 'auto_load'));
