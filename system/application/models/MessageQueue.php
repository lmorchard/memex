<?php
require_once dirname(__FILE__) . '/Model.php';

/**
 * Model managing message queue
 *
 * @TODO un-reserve a message to release it
 *
 * @TODO allow selective dequeue of messages based on subscription pattern
 * @TODO implement dependent batches that are processed in sequence
 * @TODO somehow exercise the lock on fetching a new message?
 *
 * @author l.m.orchard <l.m.orchard@pobox.com>
 * @package Memex
 */
class Memex_Model_MessageQueue extends Memex_Model
{
    protected $_table_name = 'MessageQueue';

    protected $_subscriptions;
    protected $_objs;

    const DUPLICATE_IGNORE  = 0;
    const DUPLICATE_REPLACE = 1;
    const DUPLICATE_DISCARD = 2;

    /**
     * Initialize the model.
     */
    function init() 
    {
        $this->_batch_uuid = $this->uuid();
        $this->_batch_seq = 0;
        $this->_subscriptions = array();
        $this->_objs = array();

        $this->log = Zend_Registry::get('logger');
    }

    /**
     * Subscribe to a message topic
     *
     * @param string message topic
     * @param string|object name of a class to instantiate, or an object instance
     * @param string method to invoke on the instance
     * @param mixed context data passed as second parameter to instance method
     * @return mixed Opaque subscription handle, for use with unsubscribe
     */
    public function subscribe($params)
    {
        // Accept named parameters with defaults.
        extract(array_merge(array(
            'topic'     => null,
            'object'    => null,
            'method'    => 'handleMessage',
            'context'   => null,
            'deferred'  => false,
            'priority'  => 0,
            'duplicate' => self::DUPLICATE_IGNORE
        ), $params));
        
        // Create an array for this topic, if none exists
        if (!isset($this->_subscriptions[$topic]))
            $this->_subscriptions[$topic] = array();

        // Punt on serializing object instances in deferred subscriptions that 
        // can be handled out of process.
        if ($deferred && !is_string($object)) {
            throw new Zend_Exception(
                'Object instances cannot be used in deferred subscriptions.'
            );
        }

        // Add a new subscription record.
        $this->_subscriptions[$topic][] = array(
            $object, $method, $context, $deferred, $priority, $duplicate
        );

        // Return a pointer to this subscription usable by unsubscribe.
        return array($topic, count($this->_subscriptions[$topic])-1);
    }

    /**
     * Cancel a subscription to a message topic
     *
     * @param mixed Opaque subscription handle returned by the subscribe message.
     */
    public function unsubscribe($details) 
    {
        list($topic, $idx) = $details;
        // HACK: Just set the subscription to null, rather than deal with 
        // resorting the array or whatnot.
        $this->_subscriptions[$topic][$idx] = null;
    }

    /**
     * Publish a message to a topic
     *
     * @todo Allow topic pattern matching
     *
     * @param string message topic
     * @param mixed message data
     */
    public function publish($topic, $data=null, $scheduled_for=null) {

        if (isset($this->_subscriptions[$topic])) {
            
            // Distribute the published message to subscriptions.
            foreach ($this->_subscriptions[$topic] as $subscription) {

                // Skip cancelled subscriptions
                if (null == $subscription) continue;

                // Unpack the subscription array.
                list($object, $method, $context, $deferred, $priority, $duplicate) = $subscription;

                if (!$deferred) {
                    // Handle non-deferred messages immediately.
                    $this->handle($topic, $object, $method, $context, $data);
                } else {
                    // Queue deferred messages.
                    $this->queue($topic, $object, $method, $context, $data, $priority, $scheduled_for, $duplicate);
                }

            }
        }

    }

    /**
     * Handle a message by calling the appropriate method on the specified 
     * object, instantiating it first if need be.
     *
     * @param string topic
     * @param mixed class name or object instance
     * @param string method name
     * @param mixed context data from subscription
     * @param mixed message data
     */
    public function handle($topic, $object=null, $method=null, $context=null, $body=null)
    {
        // If the first param is an array, assume it's a message array
        if (is_array($topic)) extract($topic);
        
        // One way or another, get an object for this subscription.
        if (is_object($object)) {
            $obj = $object;
        } else {
            if (!isset($this->_objs[$object])) 
                $this->_objs[$object] = new $object();
            $obj = $this->_objs[$object];
        }

        // Make a static call to default method name, or call the specified 
        // name dynamically.
        if (NULL == $method || $method == 'handleMessage') {
            $obj->handleMessage($topic, $body, $context);
        } else {
            call_user_func(array($obj, $method), $topic, $body, $context);
        }

    }

    /**
     * Queue a message for deferred processing.
     *
     * @param string topic
     * @param mixed class name or object instance
     * @param string method name
     * @param mixed context data from subscription
     * @param mixed message data
     * @param integer message priority
     * @param string scheduled time for message
     * @param integer duplicate message handling behavior
     *
     * @return array queued message data
     */
    public function queue($topic, $object, $method, $context, $data, $priority, $scheduled_for, $duplicate=self::DUPLICATE_IGNORE)
    {
        if (!is_string($object)) {
            throw new Zend_Exception(
                'Object instances cannot be used in deferred subscriptions.'
            );
        }

        $table = $this->getDbTable();

        // Encode the context and body data as JSON.
        $context = json_encode($context);
        $body    = json_encode($data);

        // Build a signature hash for this message.
        $signature = md5(join(':::', array(
            $object, $method, $context, $body
        )));

        // Check to see if anything should be done with signature duplicates.
        if ($duplicate != self::DUPLICATE_IGNORE) {

            // Look for an unreserved message with the same signature as the 
            // one about to be queued.
            $table->lock();
            $select = $table->select()
                ->where('reserved_at IS NULL')
                ->where('signature=?', $signature);
            $row = $table->fetchRow($select);

            if ($row) {
                if ($duplicate == self::DUPLICATE_REPLACE) {
                    // In replace case, delete the existing message.
                    $row->delete();
                    $table->unlock();
                } else if ($duplicate == self::DUPLICATE_DISCARD) {
                    // In discard case, fail silently.
                    $table->unlock();
                    return false;
                }
            }

        }

        // Finally insert a new message.
        $row = $table->createRow()->setFromArray(array(
            'uuid'          => $this->uuid(),
            'batch_uuid'    => $this->_batch_uuid,
            'batch_seq'     => ($this->_batch_seq++),
            'priority'      => $priority,
            'scheduled_for' => $scheduled_for,
            'topic'         => $topic,
            'object'        => $object,
            'method'        => $method,
            'context'       => $context,
            'body'          => $body,
            'signature'     => $signature
        ));
        $row->save();

        return $row->toArray();
    }

    /**
     * Reserve a message from the queue for handling.
     *
     * @return array Message data
     */
    public function reserve()
    {
        $table = $this->getDbTable();
        $table->lock();
        try {

            // Start building query to find an unreserved message.  Account for 
            // priority and FIFO.
            $select = $table->select()
                ->where('scheduled_for IS NULL OR scheduled_for < ?', date('c'))
                ->where('reserved_at IS NULL')
                ->where('finished_at IS NULL')
                ->order('priority ASC')
                ->order('created ASC')
                ->order('batch_seq ASC')
                ->limit(1);

            // Subselect to find batch UUIDs for which there are reserved 
            // messages.
            $reserved_batches_select = $table->select()->distinct()
                ->from(array('l1'=>'message_queue'), 'batch_uuid')
                ->where('reserved_at IS NOT NULL')
                ->where('finished_at IS NULL');

            // Batches are serial, so don't yield a message from an batches in 
            // which any message is already reserved.
            $select->where('batch_uuid NOT IN ('.$reserved_batches_select.')');

            // Now, try getting a message row.
            $row = $table->fetchRow($select);
            if (!$row) {
                $msg = null;
            } else {

                // Update the timestamp to reserve the message.
                $row->reserved_at = date('c');
                $row->save();

                // Convert the row to an array and decode the data blobs.
                $msg = $row->toArray();
                $msg['context'] = json_decode($msg['context'], true);
                $msg['body']    = json_decode($msg['body'], true);

            }

            // Finally, unlock the table and return the message.
            $table->unlock();
            return $msg;

        } catch (Exception $e) {
            // If anything goes wrong, be sure to unlock the table.
            $table->unlock();
            throw $e;
        }

    }

    /**
     * Mark a message as finished.
     *
     * @param string Message UUID.
     */
    public function finish($msg)
    {
        $table = $this->getDbTable();
        $select = $table->select()->where('uuid=?', $msg['uuid']);
        $row = $table->fetchRow($select);
        if (!$row) {
            throw new Exception("No such message $uuid found.");
        }
        $row->finished_at = date('c');
        $row->save();
    }

}
