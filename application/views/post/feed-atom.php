<?php
/**
 * View script to render posts as an Atom feed.
 */
$site_title = Kohana::config('config.site_title');

// Construct the site absolute base URL.
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
    'parents' => array( 'feed', 'entry', 'author' )
));

$x->feed(array('xmlns'=>'http://www.w3.org/2005/Atom'))
    ->id(url::current())
    ->title($title)
    ->updated(gmdate('c', strtotime($posts[0]['modified'])))
    ->link(array('rel'=>'alternate', 'type'=>'text/html', 'href'=>$page_home_url ))
    ;

// Add a self-reference link.
$x->link(array(
    'rel'  => 'self',
    'type' =>'application/atom+xml', 
    'href' => url::current()
));

// Add a pagination links. (RFC 5005)
extract($pagination);
$links = array(
    'first'    => 0,
    'previous' => $previous ? ($previous - 1) * $page_size : null,
    'next'     => $next ? ($next - 1) * $page_size : null,
    'last'     => $total
);
foreach ($links as $name=>$link_start) {
    if ($link_start === null) continue;
    $x->link(array(
        'rel'  => $name,
        'type' => 'application/atom+xml', 
        'href' => url::current(true, array(
            'count' => $count, 'start' => $link_start
        ))
    ));
}

// Now, finally, add all the posts as feed entries.
foreach ($posts as $post) {

    $home_url = $site_base . '/people/' . $post['screen_name'];

    $x->entry()
        ->title($post['title'])
        ->link(array( 'href'=>$post['url'] ))
        ->id($site_base . '/posts/' . out::U($post['uuid']))
        ->updated(gmdate('c', strtotime($post['user_date'])))
        ->published(gmdate('c', strtotime(empty($post['modified']) ? 
            $post['user_date'] : $post['modified'])))
        ->author()
            ->name($post['screen_name'])
            ->uri($home_url)
        ->pop();
    
    if (!empty($post['notes'])) {
        $x->summary(array('type'=>'text'), $post['notes']);
    }

    foreach ($post['tags_parsed'] as $tag) {
        $x->category(array( 
            'scheme' => $home_url, 
            'term'   => $tag
        ));
    }

    $x->pop();
}

$x->pop();

echo $x->getXML();
