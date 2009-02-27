<?php
$out = array();
foreach ($posts as $post) {
    $out[] = array(
        'u'  => $post['url'],
        'd'  => $post['title'],
        'n'  => $post['notes'],
        't'  => $post['tags_parsed'],
        'dt' => gmdate('c', strtotime($posts[0]['user_date']))
    );
}

if ($callback) {
    header('Content-Type: text/javascript');
    // Whitelist the callback to alphanumeric and a few mostly harmless
    // characters, none of which can be used to form HTML or escape a JSONP call
    // wrapper.
    $callback = preg_replace(
        '/[^0-9a-zA-Z\(\)\[\]\,\.\_\-\+\=\/\|\\\~\?\!\#\$\^\*\: \'\"]/', '', 
        $callback
    );
    echo "$callback(";
} else {
    header('Content-Type: application/json');
}

echo json_encode($out);

if ($callback) echo ')';
