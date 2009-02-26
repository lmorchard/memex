<?php
/**
 * View script to render posts as an Atom feed.
 */
$this->layout()->disableLayout();

$config = Zend_Registry::get('config');
$site_title = $config->get('site_title', 'memex');

// Construct the site absolute base URL.
$site_base = ( empty($_SERVER['HTTPS']) ? 'http://' : 'https://' ) . 
    $_SERVER['HTTP_HOST'];

if (!empty($this->screen_name)) {
    // Screen name given, so this is a profile feed.

    $title = $site_title . ' / ' . $this->screen_name;
    if ($this->tags) {
        $title .= ' / ' . join(' / ', $this->tags);
    }
    $page_home_url = $site_base . $this->url(
        array( 'screen_name' => $this->screen_name), 
        'post_profile'
    );

} else {
    // No screen name, so this is a recent or a tag feed.

    $title = $site_title . ' / ';
    if ($this->tags) {
        // Tags given, so this is a tag feed.
        $title .= 'tag / ' . join(' / ', $this->tags);
        $page_home_url = $site_base . $this->url(
            array('tags'=>join(' ', $this->tags)), 
            'post_tag'
        );
    } else {
        // No tags, so this is overall recent.
        $title .= 'recent';
        $page_home_url = $site_base . $this->url(
            array(), 'post_tag_recent'
        );
    }
}

$x = new Memex_XmlWriter(array(
    'parents' => array( 'feed', 'entry', 'author' )
));

$x->feed(array('xmlns'=>'http://www.w3.org/2005/Atom'))
    ->id($site_base . $this->url())
    ->title($title)
    ->updated(gmdate('c', strtotime($this->posts[0]['modified'])))
    ->link(array('rel'=>'alternate', 'type'=>'text/html', 'href'=>$page_home_url ))
    ;

// Add a self-reference link.
$x->link(array(
    'rel'  => 'self',
    'type' =>'application/atom+xml', 
    'href' => $site_base . $this->url()
));

// Add a first-page pagination link. (RFC 5005)
$url_first = $site_base . $this->queryUrl(array(
    'count'=>$this->count, 'start' => 0
)); 
$x->link(array(
    'rel'  => 'first',
    'type' => 'application/atom+xml', 
    'href' => $url_first
));

// Add a previous-page pagination link, if necessary. (RFC 5005)
$prev_start = $this->start - $this->count;
if ($prev_start < 0) $prev_start = 0;
if ($prev_start != $this->start) {
    $url_previous = $site_base . $this->queryUrl(array(
        'count'=>$this->count, 'start' => $prev_start
    )); 
    $x->link(array(
        'rel'  => 'previous',
        'type' => 'application/atom+xml', 
        'href' => $url_previous
    ));
}

// Add a next-page pagination link, if necessary. (RFC 5005)
$next_start = $this->start + $this->count;
if ($next_start < $this->posts_count) {
    $url_next = $site_base . $this->queryUrl(array(
        'count'=>$this->count, 'start' => $this->start + $this->count
    )); 
    $x->link(array(
        'rel'  => 'next',
        'type' => 'application/atom+xml', 
        'href' => $url_next
    ));
}

// Add a last-page pagination link, if necessary. (RFC 5005)
$url_last = $site_base . $this->queryUrl(array(
    'count'=>$this->count, 'start' => $this->posts_count - $this->count
)); 
$x->link(array(
    'rel'  => 'last',     
    'type' => 'application/atom+xml', 
    'href' => $url_last
));

// Now, finally, add all the posts as feed entries.
foreach ($this->posts as $post) {

    $home_url = $site_base . $this->url(
        array( 'screen_name' => $post['screen_name']), 
        'post_profile'
    );

    $x->entry()
        ->title($post['title'])
        ->link(array( 'href'=>$post['url'] ))
        ->id($site_base . $this->url(array('uuid'=>$post['uuid']), 'post_view'))
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
