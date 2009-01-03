<?php
return array(
    'routes_install' => array(

        'install_index' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => '',
            'defaults' => array(
                'controller' => 'install', 'action' => 'index'
            )
        ),

    ),
    'routes' => array(

        'post_profile_tags' => array(
            'type'  => 'Zend_Controller_Router_Route_Regex',
            'route' => '(.*)/(.*)',
            'defaults' => array(
                'controller' => 'post', 'action' => 'profile'
            ),
            'map' => array(
                1 => 'screen_name',
                2 => 'tags'
            ),
            'reverse' => '%s/%s',
        ),
        'post_profile' => array(
            'type'  => 'Zend_Controller_Router_Route',
            'route' => ':screen_name',
            'defaults' => array(
                'controller' => 'post', 'action' => 'profile'
            )
        ),

        'doc_index' => array(
            'type'  => 'Zend_Controller_Router_Route_Regex',
            'route' => 'docs/(.*)',
            'defaults' => array(
                'controller' => 'doc', 'action' => 'index'
            ),
            'map' => array(
                1 => 'path'
            ),
            'reverse' => 'docs/%s',
        ),


        'post_save' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => 'save',
            'defaults' => array(
                'controller' => 'post', 'action' => 'save'
            )
        ),
        'post_view' => array(
            'type'  => 'Zend_Controller_Router_Route',
            'route' => 'posts/:uuid',
            'defaults' => array(
                'controller' => 'post', 'action' => 'view'
            )
        ),
        'post_save_edit' => array(
            'type'  => 'Zend_Controller_Router_Route_Regex',
            'route' => 'posts/(.*);edit',
            'defaults' => array(
                'controller' => 'post', 'action' => 'save', 'subaction' => 'edit'
            ),
            'map' => array(
                1 => 'uuid'
            ),
            'reverse' => 'posts/%s;edit',
        ),
        'post_save_copy' => array(
            'type'  => 'Zend_Controller_Router_Route_Regex',
            'route' => 'posts/(.*);copy',
            'defaults' => array(
                'controller' => 'post', 'action' => 'save', 'subaction' => 'copy'
            ),
            'map' => array(
                1 => 'uuid'
            ),
            'reverse' => 'posts/%s;copy',
        ),
        'post_delete' => array(
            'type'  => 'Zend_Controller_Router_Route_Regex',
            'route' => 'posts/(.*);delete',
            'defaults' => array(
                'controller' => 'post', 'action' => 'delete'
            ),
            'map' => array(
                1 => 'uuid'
            ),
            'reverse' => 'posts/%s;delete',
        ),
        'post_tag_recent' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => 'recent',
            'defaults' => array(
                'controller' => 'post', 'action' => 'tag'
            )
        ),
        'post_tag' => array(
            'type'  => 'Zend_Controller_Router_Route_Regex',
            'route' => 'tag/(.*)',
            'defaults' => array(
                'controller' => 'post', 'action' => 'tag'
            ),
            'map' => array(
                1 => 'tags'
            ),
            'reverse' => 'tag/%s',
        ),
        /*
        'post_profile_tags' => array(
            'type'  => 'Zend_Controller_Router_Route_Regex',
            'route' => 'people/(.*)/(.*)',
            'defaults' => array(
                'controller' => 'post', 'action' => 'profile'
            ),
            'map' => array(
                1 => 'screen_name',
                2 => 'tags'
            ),
            'reverse' => 'people/%s/%s',
        ),
        'post_profile' => array(
            'type'  => 'Zend_Controller_Router_Route',
            'route' => 'people/:screen_name',
            'defaults' => array(
                'controller' => 'post', 'action' => 'profile'
            )
        ),
         */

        'profile_settings' => array(
            'type'  => 'Zend_Controller_Router_Route',
            'route' => 'settings',
            'defaults' => array(
                'controller' => 'profile', 'action' => 'settings'
            )
        ),

        'profile_settings_delicious' => array(
            'type'  => 'Zend_Controller_Router_Route',
            'route' => 'settings/delicious',
            'defaults' => array(
                'controller' => 'profile', 'action' => 'settings-delicious'
            )
        ),

        'profile_index' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => 'people',
            'defaults' => array(
                'controller' => 'profile', 'action' => 'index'
            )
        ),

        'auth_home' => array(
            'route' => 'home',
            'defaults' => array(
                'controller' => 'auth', 'action' => 'home'
            )
        ),

        'auth_register' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => 'register',
            'defaults' => array(
                'controller' => 'auth', 'action' => 'register'
            )
        ),
        'auth_login' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => 'login',
            'defaults' => array(
                'controller' => 'auth', 'action' => 'login'
            )
        ),
        'auth_logout' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => 'logout',
            'defaults' => array(
                'controller' => 'auth', 'action' => 'logout'
            )
        ),
        'auth_openid' => array(
            'type'  => "Zend_Controller_Router_Route_Static",
            'route' => 'openid',
            'defaults' => array(
                'controller' => 'auth', 'action' => 'openid'
            )
        ),

        'post_tag_recent' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => '',
            'defaults' => array(
                'controller' => 'post', 'action' => 'tag'
            )
        ),

    )
);
