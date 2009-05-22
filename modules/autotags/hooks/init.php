<?php
/**
 * Initialization for the Autotags module.
 *
 * @package    Memex_Autotags
 * @subpackage hooks
 * @author     l.m.orchard@pobox.com
 */
class Memex_Autotags {

    /**
     * Prefixes for known autotags.  These will be stripped out before applying 
     * new autotag rules.  Not specifying just 'system:' so as not to trample 
     * on other potential modules.
     */
    public static $autotag_prefixes = array(
        'system:filetype:', 'system:media:', 'system:has:', 
        'system:host', 'system:scheme', 'system:unfiled'
    );

    /**
     * These are tags to be applied when the associated file extension is 
     * detected in the URL path.
     */
    public static $file_ext_tags = array(
        '.mp3'  => array('system:filetype:mp3',  'system:media:audio'),
        '.wav'  => array('system:filetype:wav',  'system:media:audio'),

        '.mpg'  => array('system:filetype:mpg',  'system:media:video'),
        '.mpeg' => array('system:filetype:mpeg', 'system:media:video'),
        '.avi'  => array('system:filetype:avi',  'system:media:video'),
        '.wmv'  => array('system:filetype:wmv',  'system:media:video'),
        '.mov'  => array('system:filetype:mov',  'system:media:video'),

        '.gif'  => array('system:filetype:gif',  'system:media:image'),
        '.jpg'  => array('system:filetype:jpg',  'system:media:image'),
        '.jpeg' => array('system:filetype:jpeg', 'system:media:image'),
        '.png'  => array('system:filetype:png',  'system:media:image'),
        '.psd'  => array('system:filetype:psd',  'system:media:image'),

        '.pdf'  => array('system:filetype:pdf',  'system:media:document'),
        '.doc'  => array('system:filetype:doc',  'system:media:document'),

        '.rss'  => array('system:filetype:rss',  'system:media:feed'),
        '.atom' => array('system:filetype:atom', 'system:media:feed'),
    );

    /**
     * Initialize the module, register event listeners, etc.
     */
    public static function init()
    {
        Event::add('DecafbadUtils.layout.before_auto_render',
            array(get_class(), 'beforeAutoRender'));
        Event::add('Memex.model_posts.before_post_update',
            array(get_class(), 'applyAutoTags'));
    }

    /**
     * Perform some customizations just before layout auto-render.
     */
    public static function beforeAutoRender()
    {
        slot::append('head_end', 
            html::stylesheet('modules/autotags/public/css/autotags.css'));
        slot::append('body_end', 
            html::script('modules/autotags/public/js/autotags.js'));

        if ('post' == Router::$controller && 'profile' == Router::$method) {
            slot::append('sidebar_options',
                View::factory('autotags/options')->render());
        }
    }

    /**
     * Modify tags of a post before updating to inject tags based on rules.
     *
     * Intended to respond to Memex.model_posts.before_post_update event.
     */
    public static function applyAutoTags()
    {
        // Parse the post URL for rules ahead.
        $url_parsed = parse_url(Event::$data['url']);

        // Use the tags model to parse the tag string in the post.
        $tags_model = new Tags_Model();
        $tags_in = $tags_model->parseTags(Event::$data['tags']);

        // Prepare outgoing tags by stripping known autotags from list to have 
        // a clean slate.
        $tags_out = array_filter($tags_in, 
            array(get_class(), 'isNotAutoTag'));

        // Apply system:unfiled, if no tags are left after system tags are 
        // stripped.
        if (empty($tags_out)) {
            $tags_out[] = 'system:unfiled';
        }

        // Apply file extension tags based on the URL path.
        $path = $url_parsed['path'];
        foreach (self::$file_ext_tags as $ext => $ext_tags) {
            if ($ext == substr($path, 0-strlen($ext)))
                foreach ($ext_tags as $tag) $tags_out[] = $tag;
        }

        // Apply system:{host,scheme}={data} tags
        foreach (array('host'/*,'scheme'*/) as $part) {
            if (!empty($url_parsed[$part])) {
                $tags_out[] = "system:{$part}={$url_parsed[$part]}";
            }
        }

        // Apply system:has:{namespace} tags for locating machine tags.
        $tmp = $tags_out;
        foreach ($tmp as $tag) {
            $parts = explode(':', $tag);
            if ( (count($parts) > 1) && 'system' != $parts[0] ) {
                $tags_out[] = "system:has:{$parts[0]}";
            }
        }

        // Finally, replace the post's tags with the unique results of the 
        // applied rules.
        Event::$data['tags'] = 
            $tags_model->concatenateTags(array_unique($tags_out));
    }

    /**
     * Utility function for filtering autotags.
     *
     * @param   string  tag name
     * @returns boolean whether or not the tag is a known autotag
     */
    public static function isNotAutoTag($tag)
    {
        foreach (self::$autotag_prefixes as $prefix) {
            if (substr($tag, 0, strlen($prefix)) == $prefix) 
                return false;
        }
        return true;
    }

}
Event::add('EnvConfig.ready', array('Memex_Autotags','init'));
