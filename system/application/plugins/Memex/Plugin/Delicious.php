<?php
/**
 * Plugin to mirror post updates and deletes to a delicious account.
 */
class Memex_Plugin_Delicious 
{
    /** Base URL for delicious v1 API calls */
    public $delicious_v1_api_base_url = 'https://api.del.icio.us/v1/';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->model_helper = Zend_Controller_Action_HelperBroker::getStaticHelper('getModel');
        $this->profiles_model = $this->model_helper->getModel('Profiles');
        $this->logger = Zend_Registry::get('logger');
    }

    /**
     * Handle notification for an updated post
     */
    public function handlePostUpdated($topic, $post_data, $context)
    {
        $settings = $this->_getProfileSettings();
        if (null == $settings || !$settings[Memex_Constants::ATTRIB_DELICIOUS_ENABLED]) 
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
            if ($value && isset($data_params_map[$name]))
                $params[$data_params_map[$name]] = $value;
        }

        try {
            $this->_callDeliciousV1API(
                'posts/add', 
                $settings[Memex_Constants::ATTRIB_DELICIOUS_USER_NAME],
                $settings[Memex_Constants::ATTRIB_DELICIOUS_PASSWORD],
                $params
            );
        } catch (Exception $e) {
            $this->logger->error("delicious post for " . $post_data['uuid'] . "failed");
        }
    }

    /**
     * Handle notification for a deleted post
     */
    public function handlePostDeleted($topic, $post_data, $context)
    {
        $settings = $this->_getProfileSettings();
        if (null == $settings || !$settings[Memex_Constants::ATTRIB_DELICIOUS_ENABLED]) 
            return;
        
        try {
            $this->_callDeliciousV1API(
                'posts/add', 
                $settings[Memex_Constants::ATTRIB_DELICIOUS_USER_NAME],
                $settings[Memex_Constants::ATTRIB_DELICIOUS_PASSWORD],
                array( 'url' => $post_data['url'] )
            );
        } catch (Exception $e) {
            $this->logger->error("delicious delete for " . $post_data['uuid'] . "failed");
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
    private function _callDeliciousV1API($path='posts/update', $user_name, $password, $params)
    {
        // Build the API URL from the base, path, and query params.
        $url = $this->delicious_v1_api_base_url . '/' . $path . '?' . 
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
        $info = curl_getinfo($ch);
        curl_close($ch);

        // If the fetch wasn't successful, assume the username/password 
        // was wrong.
        if (200 != $info['http_code']) {
            throw new Exception('delicious API call failed');
        } 

        return array($info, $resp);
    }

    /**
     * Get settings for the plugin from the current profile.
     */
    private function _getProfileSettings()
    {
        $identity = Zend_Auth::getInstance()->getIdentity();
        if (!$identity) return null;

        $profile_id = $identity->default_profile['id'];
        
        $settings = $this->profiles_model->getAttributes($profile_id, array(
            Memex_Constants::ATTRIB_DELICIOUS_ENABLED,
            Memex_Constants::ATTRIB_DELICIOUS_USER_NAME,
            Memex_Constants::ATTRIB_DELICIOUS_PASSWORD
        ));
        return $settings;
    }

}
