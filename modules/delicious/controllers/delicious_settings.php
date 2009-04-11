<?php
/**
 * Delicious settings controller.
 *
 * @package    Memex_Delicious
 * @subpackage controllers
 * @author     l.m.orchard@pobox.com
 */
class Delicious_Settings_Controller extends Local_Controller 
{
    protected $auto_render = TRUE;

    public function index()
    {

    }

    private function _getReplicationValidator($data)
    {
        return Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules(Memex_Delicious::ENABLED,
                'required')
            ->add_rules(Memex_Delicious::USER_NAME,       
                'required', 'length[3,64]', 'valid::alpha_dash')
            ->add_rules(Memex_Delicious::PASSWORD,         
                'required')
            ;
    }

    public function replication()
    {
        $params = $this->getParamsFromRoute(array());

        $profile_id = $this->auth_data['profile']['id'];

        $profiles_model = new Profiles_Model();

        $attr_names = array(
            Memex_Delicious::ENABLED,
            Memex_Delicious::USER_NAME,
            Memex_Delicious::PASSWORD
        );

        if ('post' != request::method()) {
            // For a GET request, try pre-populating the form with the existing 
            // profile settings.
            $existing = $profiles_model->getAttributes($profile_id, $attr_names);
            $valid = $this->_getReplicationValidator(array_merge(
                $existing, $this->input->get()
            ));
            $valid->validate();
            $_GET = $valid->as_array();
            return;
        }

        // Try validating the POST request, populate the form, punt if invalid.
        $valid = $this->_getReplicationValidator(
            $this->input->post()
        );
        $is_valid = $valid->validate();
        $_POST = $valid->as_array();
        if (!$is_valid) {
            $this->view->set(
                'errors', $valid->errors('form_errors_delicious_settings')
            );
            return;
        }

        if (!empty($_POST[Memex_Delicious::ENABLED])) {
            // Attempt making an authenticated fetch against v1 del API
            $ch = curl_init('https://api.del.icio.us/v1/posts/update');
            curl_setopt_array($ch, array(
                CURLOPT_USERAGENT      => 'Memex/0.1',
                CURLOPT_FAILONERROR    => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERPWD => 
                    $_POST[Memex_Delicious::USER_NAME] . 
                    ':' . 
                    $_POST[Memex_Delicious::PASSWORD]
            ));
            $resp = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            // If the fetch wasn't successful, assume the username/password 
            // was wrong.
            if (200 != $info['http_code']) {
                $this->view->set('errors', array(
                    'User name and password invalid for delicious.com'
                ));
                return;
            } 

            $this->view->message =
                'Settings updated, user name and password accepted '.
                'at delicious.com';
        }

        // Update the profile settings.
        $attrs = array();
        $data  = $valid->as_array();
        foreach ($attr_names as $k) 
            $attrs[$k] = (Memex_Delicious::ENABLED==$k) ? 
                !empty($data[$k]) : $data[$k];
        $profiles_model->setAttributes($profile_id, $attrs);
    }

}
