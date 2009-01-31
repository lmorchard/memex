<?php
/**
 * Simple message queue worker.
 *
 * @author l.m.orchard <l.m.orchard@pobox.com>
 * @package Memex
 */
class Memex_MessageQueueWorker
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->log   = Zend_Registry::get('logger');
        $this->queue = Zend_Registry::get('message_queue');
        $this->init();
    }

    /**
     * Initialization hook for subclasses.
     */
    public function init()
    {
    }

    /**
     * Process messages continually.
     */
    public function run()
    {
        while (True) {
            $msg = $this->runOnce();
            if (!$msg) sleep(1);
        }
    }

    /**
     * Attempt to reserve and handle one message.
     */
    public function runOnce()
    {
        $msg = $this->queue->reserve();
        if ($msg) try {
            $this->queue->handle($msg);
            $this->queue->finish($msg);
            $this->log->debug(
                "processed {$msg['topic']} {$msg['uuid']} ".
                "{$msg['object']} {$msg['method']}"
            ); 
        } catch (Exception $e) {
            $this->log->err(
                "EXCEPTION! {$msg['topic']} {$msg['uuid']} ".
                "{$msg['object']} {$msg['method']} " . 
                $e->getMessage()
            );
        }
        return $msg;
    }

}
