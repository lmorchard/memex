<?php
/**
 * Simple helper to grab the base URL from front controller
 * see: http://framework.zend.com/wiki/display/ZFPROP/Zend_View_Helper_BaseUrl
 */
class Memex_View_Helper_BaseUrl extends Zend_View_Helper_Abstract 
{ 
    public function baseUrl($file = null) 
    { 
        // Get baseUrl 
        $config = Zend_Registry::get('config');
        $base_url = Zend_Controller_Front::getInstance()->getBaseUrl(); 
 
        // Remove trailing slashes 
        $file = ($file !== null) ? ltrim($file, '/\\') : null; 
 
        // Build return 
        $return = rtrim($base_url, '/\\') . (($file !== null) ? ('/' . $file) : ''); 
 
        return $return; 
    } 
} 
