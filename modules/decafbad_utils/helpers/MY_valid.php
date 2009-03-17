<?php
/**
 * Custom validators
 *
 * @package Memex
 * @author  l.m.orchard <l.m.orchard@pobox.com>
 */
class valid extends valid_Core 
{

    /**
     * No-op validator, just to get a field into a validator.
     */
    public static function true($str)
    {
        return true;
    }

}
