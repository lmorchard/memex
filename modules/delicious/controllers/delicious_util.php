<?php
/**
 * Delicious utility controller.
 *
 * @package    Memex_Delicious
 * @subpackage controllers
 * @author     l.m.orchard@pobox.com
 */
class Delicious_Util_Controller extends Local_Controller 
{

    public function tag_suggestions()
    {
        $url = $this->input->get('url');
        if (is_array($url)) $url = $url[0];

        $settings = Memex_Delicious::getProfileSettings(
            AuthProfiles::get_profile('id')
        );

        if (empty($settings)) {
            $out = '{}';
        } else {
            list($info, $resp) = Memex_Delicious::callDeliciousV1API(
                'posts/suggest', 
                $settings[Memex_Delicious::USER_NAME],
                $settings[Memex_Delicious::PASSWORD],
                array(
                    'format' => 'json',
                    'url' => $url 
                )
            );
            $out = $resp;
        }

        header('Content-Type: application/json');
        $this->auto_render = FALSE;
        echo $out;
    }

}
