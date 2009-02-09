<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * @package  Core
 *
 * Supported Shortcuts:
 *  :any - matches any non-blank string
 *  :num - matches any number
 */

$config['register'] = 'auth/register';
$config['login']    = 'auth/login';
$config['logout']   = 'auth/logout';

/*
$config[';edit']     = 'main/edit';
$config['(.*);edit'] = 'main/edit/$1';
*/

/*
$config['signup'] = 'auth/signup';
$config['login']  = 'auth/login';
$config['logout'] = 'auth/logout';

$config['home']   = 'profiles/home';

$config['profiles/(.*);edit'] = 
    'profiles/edit/$1';

$config['profiles/(.*)/entries'] = 
    'entries/index/$1';

$config['profiles/(.*)/entries/(.*);delete'] = 
    'entries/delete/$1/$2';

$config['profiles/(.*)/entries/(.*)'] = 
    'entries/view/$1/$2';

$config['profiles/(.*)/avatars'] = 
    'profiles/avatars/$1';
$config['profiles/(.*)/avatars/current'] = 
    'profiles/current_avatar/$1';

$config['profiles/(.*)'] = 
    'profiles/view/$1';
$config['~(.*)'] = 
    'profiles/view/$1';
 */

$config['captcha/default'] = 'captcha/index';

/**
 * Permitted URI characters. Note that "?", "#", and "=" are URL characters, and
 * should not be added here.
 */
$config['_allowed'] = '-a-z 0-9~%.,:;_/';

/**
 * Default route to use when no URI segments are available.
 */
$config['_default'] = 'main';
