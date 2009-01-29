<?php
/**
 * Actions to help process and maintain the message queue
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
        header('Content-Type: application/json');

        $worker = new Memex_MessageQueueWorker();
        $msg = $worker->runOnce();

        if ($msg) {
            echo json_encode(array(
                'uuid' => $msg['uuid'] 
            ));
        } else {
            header('HTTP/1.1 304 Not Modified');
            echo '{}';
        }
    }

}
