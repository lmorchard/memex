<?php
/**
 * Initialization for the Delicious module.
 *
 * @package    Memex_Delicious
 * @subpackage hooks
 * @author     l.m.orchard@pobox.com
 */
class Memex_Delicious {

    const ENABLED   = 'delicious_enabled';
    const USER_NAME = 'delicious_user_name';
    const PASSWORD  = 'delicious_password';

    /** Base URL for delicious v1 API calls */
    public static $delicious_v1_api_base_url = 'https://api.del.icio.us/v1/';

    /**
     * Initialize the Delicious module, register event listeners, etc.
     */
    public static function init()
    {
        Event::add('Memex.pre_settings_menu', 
            array('Memex_Delicious', 'buildSettingsMenu'));

        Event::add('DecafbadUtils.layout.before_auto_render',
            array('Memex_Delicious', 'beforeAutoRender'));

        DeferredEvent::add('Memex.model_posts.post_updated', 
            array('Memex_Delicious', 'handlePostUpdated'));
        DeferredEvent::add('Memex.model_posts.post_deleted', 
            array('Memex_Delicious', 'handlePostDeleted'));
    }

    /**
     * Provide content to the profile settings menu on event trigger.
     */
    public static function buildSettingsMenu()
    {
        $name = Event::$data['auth_data']['profile']['screen_name'];

        Event::$data['sections'][] = array(
            'title' => 'Delicious',
            'items' => array(
                array(
                    'url' => 
                        'profiles/' . rawurlencode($name) . '/settings/delicious/import',
                    'title' => 
                        'delicious.com item import',
                    'description' => 
                        'import items from a delicious.com account'
                ),
                array(
                    'url' => 
                        'profiles/' . rawurlencode($name) . '/settings/delicious/replication',
                    'title' => 
                        'delicious.com activity replication',
                    'description' => 
                        'copy item updates and deletions to a delicious.com account'
                )
            )
        );
    }

    /**
     * Perform customizations just before layout auto-render.
     */
    public static function beforeAutoRender()
    {
        slot::append('head_end', 
            html::stylesheet('modules/delicious/public/css/main.css'));
        slot::append('body_end', 
            html::script('modules/delicious/public/js/main.js'));

        if ('post' == Router::$controller && 'save' == Router::$method) {
            slot::append('head_end', 
                html::stylesheet('modules/delicious/public/css/postsave.css'));
            slot::append('body_end', 
                html::script('modules/delicious/public/js/postsave.js'));
            slot::append('form_end',
                View::factory('delicious_util/tag_suggestions')->render());
        }
    }

    /**
     * Used to filter out system: tags before posting bookmark to delicious.
     *
     * @param  string  tag
     * @return boolean whether or not tag starts with system:
     */
    private static function _isNotSystem($tag)
    {
        return !( 0 === strpos($tag, 'system:') );
    }

    /**
     * Replicate post updates to delicious.com, if enabled.
     */
    public static function handlePostUpdated()
    {
        $post_data = Event::$data;

        Kohana::log('debug', 'need to post to delicious');
        Kohana::log('debug', var_export($post_data,true));

        if (Kohana::config('model.batch_mode'))
            return;

        $settings = self::getProfileSettings($post_data['profile_id']);
        if (null == $settings || !$settings[self::ENABLED]) 
            return;

        $data_params_map = array(
            'url'       => 'url', 
            'title'     => 'description',
            'notes'     => 'extended', 
            'tags'      => 'tags',
            'user_date' => 'dt'
        );
        $params = array();
        foreach ($post_data as $name=>$value) {
            if ('tags' == $name) {
                $tags_model = new Tags_Model();
                $value = $tags_model->concatenateTags(
                    array_filter(
                        $tags_model->parseTags($value), 
                        array(get_class(), '_isNotSystem')
                    )
                );
            }
            if ($value && isset($data_params_map[$name]))
                $params[$data_params_map[$name]] = $value;
        }

        try {
            self::callDeliciousV1API(
                'posts/add', 
                $settings[self::USER_NAME],
                $settings[self::PASSWORD],
                $params
            );
            Kohana::log('debug', "delicious post for {$post_data['uuid']} success");
        } catch (Exception $e) {
            Kohana::log('error', "delicious post for {$post_data['uuid']} failed");
        }
    }

    /**
     * Replicate post deletes to delicious.com, if enabled.
     */
    public static function handlePostDeleted()
    {
        $post_data = Event::$data;

        if (Kohana::config('model.batch_mode'))
            return;

        $settings = self::getProfileSettings($post_data['profile_id']);
        if (null == $settings || !$settings[self::ENABLED]) 
            return;
        
        try {
            self::callDeliciousV1API(
                'posts/delete', 
                $settings[self::USER_NAME],
                $settings[self::PASSWORD],
                array( 'url' => $post_data['url'] )
            );
        } catch (Exception $e) {
            Kohana::log('err', "delicious delete for " . $post_data['uuid'] . "failed");
        }
    }

    /**
     * Make a call to the delicious v1 API
     *
     * @param string path to the API call
     * @param string user name
     * @param string password
     * @param array API call parameters
     * @return array cURL request info and response
     */
    public static function callDeliciousV1API($path='posts/update', $user_name, $password, $params)
    {
        // Build the API URL from the base, path, and query params.
        $url = self::$delicious_v1_api_base_url . '/' . $path . '?' . 
            http_build_query($params);

        // Attempt making an authenticated fetch against v1 del API
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_USERAGENT      => 'Memex/0.1',
            CURLOPT_FAILONERROR    => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERPWD        => $user_name . ':' . $password
        ));
        $resp = curl_exec($ch);
        Kohana::log('debug', 'del API resp: ' . $resp);
        $info = curl_getinfo($ch);
        curl_close($ch);

        // If the fetch wasn't successful, assume the username/password 
        // was wrong.
        if (200 != $info['http_code']) {
            throw new Exception('delicious API call failed');
        } 
        if (FALSE !== strpos($resp, 'something went wrong')) {
            throw new Exception('delicious API call failed');
        }

        return array($info, $resp);
    }

    /**
     * Get settings for the plugin from the current profile.
     */
    public static function getProfileSettings($profile_id)
    {
        $profiles_model = new Profiles_Model();
        $settings = $profiles_model->getAttributes($profile_id, array(
            self::ENABLED, self::USER_NAME, self::PASSWORD
        ));
        return $settings;
    }
}
Event::add('EnvConfig.ready', array('Memex_Delicious','init'));
