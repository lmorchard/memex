<?php
$site_title = Kohana::config('config.site_title');

$site_base = ( empty($_SERVER['HTTPS']) ? 'http://' : 'https://' ) . 
    $_SERVER['HTTP_HOST'];

if (!empty($screen_name)) {
    // Screen name given, so this is a profile feed.
    $title = $site_title . ' / ' . $screen_name;
    if ($tags) $title .= ' / ' . join(' / ', $tags);
    $page_home_url = $site_base . '/people/' . $screen_name;
} else {
    // No screen name, so this is a recent or a tag feed.
    $title = $site_title . ' / ';
    if ($tags) {
        // Tags given, so this is a tag feed.
        $title .= 'tag / ' . join(' / ', $tags);
        $page_home_url = $site_base . '/tag/' . out::U(join(' ', $tags));
    } else {
        // No tags, so this is overall recent.
        $title .= 'recent';
        $page_home_url = $site_base . '/recent';
    }
}
$x = new Memex_XmlWriter(array(
    'parents' => array( 'rss', 'channel', 'item' )
));

$x->rss(array('version' => '2.0'))
    ->channel()
        ->title($title)
        // ->description(Kohana::config('config.site_subtitle'))
        ->pubDate(date('r',  strtotime($posts[0]['modified'])))
        ->link($page_home_url)
        //->managingEditor(
        //    Kohana::config('config.site_author_name') .
        //    '<'.Kohana::config('config.site_author_email').'>'
        //)
        ;

foreach ($posts as $post) {

    $home_url = $site_base . '/people/' . $post['screen_name'];

    $x->item()
        ->title($post['title'])
        ->link($post['url'])
        ->guid($site_base . '/posts/' . out::U($post['uuid']))
        ->pubDate(date('r', strtotime($post['user_date'])));

    if (!empty($post['notes'])) {
        $x->description($post['notes']);
    }

    foreach ($post['tags_parsed'] as $tag) {
        $x->category(array('domain' => $home_url), $tag);
    }
    
    $x->pop();
}

$x->pop();
$x->pop();

echo $x->getXML();
