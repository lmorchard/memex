<?php
/**
 * Template text encoding / escaping shortcuts helper.
 *
 * @package    DecafbadUtils
 * @subpackage helpers
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class out_Core
{

    /**
     * Escape a string for HTML inclusion.
     *
     * @param string content to escape
     * @return string HTML-encoded content
     */
    public static function H($s, $echo=FALSE) {
        $out = htmlentities($s, ENT_QUOTES, 'utf-8');
        if ($echo) echo $out;
        else return $out;
    }

    /**
     * Encode a string for URL inclusion.
     *
     * @param string content to encode
     * @return string URL-encoded content
     */
    public static function U($s, $echo=FALSE) {
        $out = rawurlencode($s);
        if ($echo) echo $out;
        else return $out;
    }

    /**
     * JSON-encode a value
     *
     * @param mixed some data to be encoded
     * @return string JSON-encoded data
     */
    public static function JSON($s, $echo=FALSE) {
        $out = json_encode($s);
        if ($echo) echo $out;
        else return $out;
    }

}
