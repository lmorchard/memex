<?php
/**
 * Initialization for the Memex main application.
 *
 * @author l.m.orchard <l.m.orchard@pobox.com>
 * @package Memex
 */
class Memex_Init {

    /**
     * Initialize the application.
     */
    public static function init()
    {
        Event::add('Memex.pre_settings_menu', 
            array('Memex_Init', 'buildSettingsMenu'));
        Event::add('Memex.model_posts.post_updated', 
            array('Memex_Init', 'handlePostUpdated'));
        Event::add('Memex.model_posts.post_deleted', 
            array('Memex_Init', 'handlePostDeleted'));
    }

    /**
     * Update tag indexing for updated posts.
     */
    public static function handlePostUpdated()
    {
        $tags_model = new Tags_Model();
        $tags_model->updateTagsForPost(Event::$data);
    }

    /**
     * Delete indexed tags for updated posts.
     */
    public static function handlePostDeleted()
    {
        $tags_model = new Tags_Model();
        $tags_model->deleteTagsForPost(Event::$data['id']);
    }

    /**
     * Build items for the profile settings menu.
     */
    public static function buildSettingsMenu()
    {
        $name = Event::$data['auth_data']['profile']['screen_name'];

        Event::$data['sections'][] = array(
            'title' => 'Basics',
            'priority' => 999,
            'items' => array(
                array(
                    'url' => 'profiles/' . out::U($name) . '/settings/basics/details',
                    'title' => 'Edit profile details',
                    'description' => 'change screen name, bio, etc.'
                ),
                array(
                    'url' => 'profiles/' . out::U($name) . '/settings/basics/password',
                    'title' => 'Change login password',
                    'description' => 'change current login password'
                ),
                array(
                    'url' => 'profiles/' . out::U($name) . '/settings/basics/logins',
                    'title' => 'Manage profile logins',
                    'description' => 'create and remove logins for this profile'
                ),
                array(
                    'url' => 'profiles/' . out::U($name) . '/settings/basics/delete',
                    'title' => 'Delete profile',
                    'description' => 'delete this profile altogether'
                ),
            )
        );
    }
}
Event::add('LocalConfig.ready', array('Memex_Init','init'));
