<?php defined('SYSPATH') or die('No direct access allowed.');

if (Kohana::config('core.needs_installation')) {

    $config['_default'] = 'install/index';

} else {

    $config['_allowed'] = '-a-z 0-9~%.,:;_/';

    $config['home'] =     'auth/home';
    $config['register'] = 'auth/register';
    $config['login'] =    'auth/login';
    $config['logout'] =   'auth/logout';

    $config['profiles/([^/]+)/settings'] = 'profile/settings/screen_name/$1';
    $config['profiles']                  = 'profile/index';

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

    $config['feeds/([^/]+)/recent']              = 'post/tag/is_feed/true/format/$1';
    $config['feeds/([^/]+)/recent/(.*)']         = 'post/tag/is_feed/true/format/$1/tags/$2';
    $config['feeds/([^/]+)/tag/(.*)']            = 'post/tag/is_feed/true/format/$1/tags/$2';
    $config['feeds/([^/]+)/people/([^/]+)/(.*)'] = 'post/profile/is_feed/true/format/$1/screen_name/$2/tags/$3';
    $config['feeds/([^/]+)/people/([^/]+)']      = 'post/profile/is_feed/true/format/$1/screen_name/$2';
    $config['feeds/(.*)']                        = 'post/tag/is_feed/true/format/$1';

    $config['tag/(.*)'] = 'post/tag/tags/$1';
    $config['recent']   = 'post/tag';

    $config['queue/runonce']      = 'messagequeue/runonce/format/json';
    $config['queue/runonce/(.*)'] = 'messagequeue/runonce/format/$1';

    $config['_default'] = 'post/tag';

    $config['captcha/default'] = 'captcha/index';

}
