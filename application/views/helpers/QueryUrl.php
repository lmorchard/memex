<?php
/**
 * Simple helper to grab the base URL from front controller
 * see: http://framework.zend.com/wiki/display/ZFPROP/Zend_View_Helper_BaseUrl
 */
class Memex_View_Helper_QueryUrl extends Zend_View_Helper_Abstract 
{ 
    /**
     * Generates an url given the name of a route.
     *
     * @access public
     *
     * @param  array $urlOptions Options passed to the assemble method of the Route object.
     * @param  mixed $name The name of a Route to use. If null it will use the current Route
     * @param  bool $reset Whether or not to reset the route defaults with those provided
     * @return string Url for the link href attribute.
     */
    public function queryUrl(array $params=array(), array $urlOptions = array(), $name = null, $reset = false, $encode = true)
    { 
        $router = Zend_Controller_Front::getInstance()->getRouter();
        return $router->assemble($urlOptions, $name, $reset, $encode) .
            '?' . http_build_query(array_merge($_GET, $params));
    } 
} 
