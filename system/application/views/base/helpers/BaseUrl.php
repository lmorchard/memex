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
        $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl(); 
 
        // Remove trailing slashes 
        $file = ($file !== null) ? ltrim($file, '/\\') : null; 
 
        // Build return 
        $return = rtrim($baseUrl, '/\\') . (($file !== null) ? ('/' . $file) : ''); 
 
        return $return; 
    } 
} 
