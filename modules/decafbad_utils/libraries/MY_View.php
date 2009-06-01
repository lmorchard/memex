<?php
/**
 * Customizations to the core view class.
 *
 * @package    DecafbadUtils
 * @subpackage libraries
 * @author     l.m.orchard@pobox.com
 */
class View extends View_Core
{

    /**
     * Get the current filename set for the view.
     *
     * @return string
     */
    function get_filename()
    {
        return $this->kohana_filename;
    }

}
