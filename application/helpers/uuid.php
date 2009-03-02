<?php
/**
 * Generate UUIDs
 *
 * @package Memex
 * @author  l.m.orchard <l.m.orchard@pobox.com>
 */
class uuid_Core
{

    /**
     * Produce a UUID per RFC 4122, version 4 
     * See also: http://us.php.net/manual/en/function.uniqid.php#69164
     */
    public static function uuid() 
    {
        require_once('application/vendor/OmniTI/UUID.php');
        $u = new OmniTI_UUID();
        return $u->toRFC4122String();
    }

}
