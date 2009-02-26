<?php
/**
 * View script to render posts as JSON, with callback support.
 */
$this->layout()->disableLayout();

$out = array();
foreach ($this->posts as $post) {
    $out[] = array(
        'u'  => $post['url'],
        'd'  => $post['title'],
        'n'  => $post['notes'],
        't'  => $post['tags_parsed'],
        'dt' => gmdate('c', strtotime($this->posts[0]['user_date']))
    );
}

if ($this->callback) {
    // Whitelist the callback to alphanumeric and a few mostly harmless
    // characters, none of which can be used to form HTML or escape a JSONP call
    // wrapper.
    $callback = preg_replace(
        '/[^0-9a-zA-Z\(\)\[\]\,\.\_\-\+\=\/\|\\\~\?\!\#\$\^\*\: \'\"]/', '', 
        $this->callback
    );
    echo "$callback(";
}
echo $this->json($out);
if ($callback) echo ')';
