<?php return array(
    'messaging' => array(

        array('topic'=>'Memex_Model_Posts/postUpdated', 'object'=>'Memex_Model_Tags', 'method'=>'handlePostUpdated'),
        array('topic'=>'Memex_Model_Posts/postDeleted', 'object'=>'Memex_Model_Tags', 'method'=>'handlePostDeleted'),

        array('topic'=>'Memex_Model_Posts/postUpdated', 'object'=>'Memex_Plugin_Delicious', 'method'=>'handlePostUpdated', 'deferred'=>true, 'priority'=>100),
        array('topic'=>'Memex_Model_Posts/postDeleted', 'object'=>'Memex_Plugin_Delicious', 'method'=>'handlePostDeleted', 'deferred'=>true, 'priority'=>100),

    )
);
