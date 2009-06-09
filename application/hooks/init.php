<?php
/**
 * Initialization for the Memex main application.
 *
 * @package    Memex
 * @subpackage hooks
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Memex_Init {

    private static $tags_model;

    /**
     * Initialize the application.
     */
    public static function init()
    {
        Event::add('auth_profiles.before_settings_menu', 
            array('Memex_Init', 'buildSettingsMenu'));
        Event::add('Memex.model_posts.post_updated', 
            array('Memex_Init', 'handlePostUpdated'));
        Event::add('Memex.model_posts.post_deleted', 
            array('Memex_Init', 'handlePostDeleted'));

        self::$tags_model = new Tags_Model();
    }

    /**
     * Update tag indexing for updated posts.
     */
    public static function handlePostUpdated()
    {
        self::$tags_model->updateTagsForPost(Event::$data);
    }

    /**
     * Delete indexed tags for updated posts.
     */
    public static function handlePostDeleted()
    {
        self::$tags_model->deleteTagsForPost(Event::$data);
    }

    /**
     * Build items for the profile settings menu.
     */
    public static function buildSettingsMenu()
    {
        /*
        Event::$data['sections'][] = array(
            );
         */
    }
}
Memex_Init::init();
// Event::add('EnvConfig.ready', array('Memex_Init','init'));
