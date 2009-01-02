<?php
/**
 * Publish/subscribe messaging center
 *
 * @TODO: Work on enabling deferred messages for offline processing.
 */
class Memex_NotificationCenter
{
    protected $_subscriptions;
    protected $_objs;

    /**
     * Singleton instance
     *
     * @return Memex_NotificationCenter
     */
    protected static $_instance = null;
    public static function getInstance() {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    protected function __construct() 
    {
        $this->_subscriptions = array();
        $this->_objs = array();
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
    public function subscribe($topic, $obj_or_class_name=null, $method_name='handleMessage', $context=null) 
    {
        // Create an array for this topic, if none exists
        if (!isset($this->_subscriptions[$topic]))
            $this->_subscriptions[$topic] = array();

        // Add a new subscription record.
        $this->_subscriptions[$topic][] = array(
            $obj_or_class_name, $method_name, $context
        );

        // Return a pointer to this subscription usable by unsubscribe.
        return array($topic, count($this->_subscriptions[$topic])-1);
    }

    /**
     * Cancel a subscription to a message topic
     *
     * @param mixed Opaque subscription handle returned by the subscribe message.
     */
    public function unsubscribe($details) {
        list($topic, $idx) = $details;
        // HACK: Just set the subscription to null, rather than deal with 
        // resorting the array or whatnot.
        $this->_subscriptions[$topic][$idx] = null;
    }

    /**
     * Publish a message to a topic
     *
     * @param string message topic
     * @param mixed message data
     */
    public function publish($topic, $data=null) {

        if (isset($this->_subscriptions[$topic])) {
            foreach ($this->_subscriptions[$topic] as $subscription) {

                // Skip cancelled subscriptions
                if (null == $subscription) continue;

                // Unpack the subscription array.
                list($obj_or_class_name, $method_name, $context) = 
                    $subscription;

                // One way or another, get an object for this subscription.
                if (is_object($obj_or_class_name)) {
                    // If the subscription is already an object, use it.
                    $obj = $obj_or_class_name;
                } else {
                    if (!isset($this->_objs[$obj_or_class_name])) {
                        // Instantiate a new object of the class if we don't 
                        // already have one.
                        // TODO: Use Zend_Loader or something here?
                        $this->_objs[$obj_or_class_name] = 
                            new $obj_or_class_name();
                    }
                    // Use the cached object instance.
                    $obj = $this->_objs[$obj_or_class_name];
                }

                if (NULL == $method_name || $method_name == 'handleMessage') {
                    // If using the default message handler, call it directly
                    $obj->handleMessage($topic, $data, $context);
                } else {
                    // Otherwise, do a dynamic call to the named method.
                    call_user_func(
                        array($obj, $method_name), 
                        $topic, $data, $context
                    );
                }

            }
        }
    }

}
