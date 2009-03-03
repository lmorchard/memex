<?php

$config['profiles/([^/]+)/settings/delicious/import'] = 
    'delicious_settings/import/screen_name/$1';
$config['profiles/([^/]+)/settings/delicious/replication'] = 
    'delicious_settings/replication/screen_name/$1';

$config['api/v1/posts/update'] = 'delicious_api/posts_update';
$config['api/v1/posts/all']    = 'delicious_api/posts_all';
$config['api/v1/posts/recent'] = 'delicious_api/posts_recent';
$config['api/v1/posts/get']    = 'delicious_api/posts_get';
$config['api/v1/posts/dates']  = 'delicious_api/posts_dates';
$config['api/v1/posts/add']    = 'delicious_api/posts_add';
$config['api/v1/posts/delete'] = 'delicious_api/posts_delete';
$config['api/v1/tags/get']     = 'delicious_api/tags_get';
$config['api/v1/tags/all']     = 'delicious_api/tags_all';
$config['api/v1/tags/add']     = 'delicious_api/tags_add';
$config['api/v1/tags/delete']  = 'delicious_api/tags_delete';
$config['api/v1/tags/rename']  = 'delicious_api/tags_rename';
