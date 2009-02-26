<?php defined('SYSPATH') or die('No direct access allowed.');

$config['home']     = 'auth/home';
$config['register'] = 'auth/register';
$config['login']    = 'auth/login';
$config['logout']   = 'auth/logout';

$config['save']              = 'post/save';
$config['posts/(.*);edit']   = 'post/save/uuid/$1/submethod/edit';
$config['posts/(.*);copy']   = 'post/save/uuid/$1/submethod/copy';
$config['posts/(.*);delete'] = 'post/delete/uuid/$1';
$config['posts/(.*)']        = 'post/view/uuid/$1';

$config['docs/(.*)'] = 'doc/index/path/$1';
$config['docs']      = 'doc/index';

$config['~([^/]+)/(.*)']       = 'post/profile/screen_name/$1/tags/$2';
$config['~(.*)']               = 'post/profile/screen_name/$1';
$config['people/([^/]+)/(.*)'] = 'post/profile/screen_name/$1/tags/$2';
$config['people/(.*)']         = 'post/profile/screen_name/$1';

$config['feeds/([^/]+)/recent']       = 'post/tag/is_feed/true/format/$1';
$config['feeds/([^/]+)/recent/(.*)']  = 'post/tag/is_feed/true/format/$1/tags/$2';
$config['feeds/([^/]+)/tag/(.*)']     = 'post/tag/is_feed/true/format/$1/tags/$2';
$config['feeds/([^/]+)/([^/]+)/(.*)'] = 'post/profile/is_feed/true/format/$1/screen_name/$2/tags/$3';
$config['feeds/(.*)']                 = 'post/tag/is_feed/true/format/$1';

$config['tag/(.*)'] = 'post/tag/tags/$1';
$config['recent']   = 'post/tag';

$config['_default'] = 'post/tag';

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
//$config['_default'] = 'main';
