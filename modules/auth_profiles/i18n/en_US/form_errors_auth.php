<?php
/**
 * Registration form error messages
 *
 * @package Memex
 * @author  l.m.orchard@pobox.com
 */
$lang = array(
    'login_name' => array(
        'required'             
            => 'Login name is required.',
        'length'               
            => 'Login name must be between 3 and 64 characters in length',
        'alpha_dash'           
            => 'Login name must contain only alphanumeric characters',
        'isLoginNameAvailable' 
            => 'Login name is not available.'
    ),
    'email' => array(
        'required' 
            => 'Email address is required.',
        'email'
            => 'Valid email address is required.'
    ),
    'password' => array(
        'default'
            => 'Password is invalid.',
        'required' 
            => 'Password is required.'
    ),
    'password_confirm' => array(
        'required' 
            => 'Password confirmation is required.',
        'matches'  
            => 'Password and confirmation must match.'
    ),
    'screen_name' => array(
        'required'              
            => 'Screen name is required.',
        'length'                
            => 'Screen name must be between 3 and 64 characters in length',
        'alpha_dash'            
            => 'Screen name must contain only alphanumeric characters',
        'isScreenNameAvailable' 
            => 'Screen name is not available.',
    ),
    'full_name' => array(
        'required'      
            => 'Full name is required',
        'standard_text' 
            => 'Full name must contain only alphanumeric characters'
    ),
    'captcha' => array(
        'default' 
            => 'Valid captcha response is required.'
    ),
);
