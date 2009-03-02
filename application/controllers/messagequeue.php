<?php
/**
 * Actions to help process and maintain the message queue
 *
 * @todo admin methods to view queue statistics, health, etc
 * @todo personal methods to see reports on messages per profile
 *
 * @author l.m.orchard <l.m.orchard@pobox.com>
 * @package Memex
 */
class Messagequeue_Controller extends Controller
{ 
    protected $auto_render = TRUE;
    
    /**
     * Run one processing loop on the queue and output status in JSON.
     */
    public function runonce()
    {
        $params = $this->getParamsFromRoute(array(
            'format' => 'json'
        ));

        $mq = new MessageQueue_Model();
        $msg = $mq->runOnce();

        if ($params['format'] == 'json') {

            if ($msg) {
                // Report the UUID of the message.
                // TODO: Only release this info on a debug setting?
                $out = json_encode(array(
                    'uuid' => $msg['uuid'] 
                ));
            } else {
                // If no message available, throw a 304 header.
                header('HTTP/1.1 304 Not Modified');
                $out = '{}';
            }

            if (!isset($_GET['callback'])) {
                $callback = FALSE;
            } else {
                $callback = preg_replace(
                    '/[^0-9a-zA-Z\(\)\[\]\,\.\_\-\+\=\/\|\\\~\?\!\#\$\^\*\: \'\"]/', '', 
                    $_GET['callback']
                );
            }

            $this->auto_render = FALSE;
            $this->view = null;
            if ($callback) {
                header('Content-Type: text/javascript');
                echo "$callback($out)";
            } else {
                header('Content-Type: application/json');
                echo $out;
            }

        } else {

            $this->auto_render = FALSE;
            var_dump($msg);

        }

    }

}
