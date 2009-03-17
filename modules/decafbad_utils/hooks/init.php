<?php
/**
 * Initialization for the DecafbadUtils module
 *
 * @package    DecafbadUtils
 * @subpackage hooks
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class DecafbadUtils_Init {

    /**
     * Initialize the application.
     */
    public static function init()
    {
        require_once(Kohana::find_file('vendor', 'Markdown'));
    }
}
DecafbadUtils_Init::init();
