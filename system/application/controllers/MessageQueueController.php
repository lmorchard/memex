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
class MessageQueueController extends Zend_Controller_Action  
{ 
    
    /**
     * Run one processing loop on the queue and output status in JSON.
     */
    public function runAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $worker = new Memex_MessageQueueWorker();
        $msg = $worker->runOnce();

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

        header('Content-Type: application/json');
        if ($callback) {
            echo "$callback($out)";
        } else {
            echo $out;
        }

    }

}
