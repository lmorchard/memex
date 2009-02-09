<?php
/**
 *
 * @package Memex
 * @author  l.m.orchard@pobox.com
 */
class html extends html_Core
{

    /**
     * Wrap an array of items in <li> tags inside a <ul>
     */
    public static function ul($arr)
    {
        $out = array();
        foreach ($arr as $item) {
            if (is_array($item))
                $item = join("\n", $item);
            $out[] = $item;
            // $out[] = "<li>$item</li>";
        }
        return '<ul>'.join("\n", $out).'</ul>';
    }

}
