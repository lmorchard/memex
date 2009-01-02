<?php
// Call Memex_Model_LoginsTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "Memex_NotificationCenterTest::main");
}

require_once dirname(__FILE__) . '/../TestHelper.php';

require_once 'Memex/NotificationCenter.php';

/**
 * Test class for Memex_NotificationCenter
 *
 * @group Models
 */
class Memex_NotificationCenterTest extends PHPUnit_Framework_TestCase 
{
    /**
     * Runs the test methods of this class.
     *
     * @return void
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite("Memex_NotificationCenterTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
    }

    /**
     * This method is called after a test is executed.
     *
     * @return void
     */
    public function tearDown()
    {
    }

    /**
     * Exercise subscribe, unsubscribe, and publish
     */
    public function testSubscribeUnsubscribePublish()
    {
        $nc = Memex_NotificationCenter::getInstance();

        $log = Memex_NotificationCenterTest_LogCollector::getInstance();

        $l1 = new Memex_NotificationCenterTest_Listener('l1');
        $l2 = new Memex_NotificationCenterTest_Listener('l2');
        $l3 = new Memex_NotificationCenterTest_Listener('l3');
        $l4 = new Memex_NotificationCenterTest_Listener('l4');
        
        $s1 = $nc->subscribe('t1', $l1);
        $s2 = $nc->subscribe('t2', $l2, 'h1');
        $s3 = $nc->subscribe('t2', 'Memex_NotificationCenterTest_Listener', 'h3', 'c3');
        $s4 = $nc->subscribe('t3', $l3, 'h2', 'c1');
        $s5 = $nc->subscribe('t3', 'Memex_NotificationCenterTest_Listener', 'h2', 'c1');
        $s6 = $nc->subscribe('t4', $l4, 'h3', 'c2');
        $s7 = $nc->subscribe('t5', 'Memex_NotificationCenterTest_Listener');

        $nc->publish('t1'); 
        $nc->publish('t1', 'd1'); 
        $nc->publish('t2', 'd2'); 
        $nc->publish('t3', 'd3'); 
        $nc->publish('t4', 'd4'); 
        $nc->publish('t5', 'd5'); 

        $this->assertEquals(
            array(
                'l1 h0 t1 NU NU',
                'l1 h0 t1 d1 NU',
                'l2 h1 t2 d2 NU',
                'l0 h3 t2 d2 c3',
                'l3 h2 t3 d3 c1',
                'l0 h2 t3 d3 c1',
                'l4 h3 t4 d4 c2',
                'l0 h0 t5 d5 NU'
            ),
            $log->log
        );

        $log->reset();

        $nc->unsubscribe($s1);
        $nc->unsubscribe($s3);
        $nc->unsubscribe($s4);
        $nc->unsubscribe($s6);

        $nc->publish('t1'); 
        $nc->publish('t1', 'd1'); 
        $nc->publish('t2', 'd2'); 
        $nc->publish('t3', 'd3'); 
        $nc->publish('t4', 'd4'); 
        $nc->publish('t5', 'd5'); 

        $this->assertEquals(
            array(
                'l2 h1 t2 d2 NU',
                'l0 h2 t3 d3 c1',
                'l0 h0 t5 d5 NU'
            ),
            $log->log
        );

        $log->reset();

        $nc->unsubscribe($s2);
        $nc->unsubscribe($s5);
        $nc->unsubscribe($s7);

        $nc->publish('t1'); 
        $nc->publish('t1', 'd1'); 
        $nc->publish('t2', 'd2'); 
        $nc->publish('t3', 'd3'); 
        $nc->publish('t4', 'd4'); 
        $nc->publish('t5', 'd5'); 

        $this->assertEquals(
            array(
            ),
            $log->log
        );

    }
    
}

class Memex_NotificationCenterTest_LogCollector
{
    public $log;

    protected static $_instance = null;
    public static function getInstance() {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->reset();
    }

    public function log($msg) {
        $this->log[] = $msg;
    }

    public function reset() {
        $this->log = array();
    }
}

class Memex_NotificationCenterTest_Listener
{
    public $log;
    public $id;

    public function __construct($id='l0') {
        $this->id = $id;
        $this->log = array();
    }

    private function _log($method, $topic, $data, $context) {
        if ($data == null) $data = 'NU';
        if ($context == null) $context = 'NU';
        Memex_NotificationCenterTest_LogCollector::getInstance()->log(
            $this->id . " $method $topic $data $context"
        );
    }
    
    public function handleMessage($topic, $data, $context) {
        $this->_log('h0', $topic, $data, $context);
    }

    public function h1($topic, $data, $context) {
        $this->_log('h1', $topic, $data, $context);
    }

    public function h2($topic, $data, $context) {
        $this->_log('h2', $topic, $data, $context);
    }

    public function h3($topic, $data, $context) {
        $this->_log('h3', $topic, $data, $context);
    }

}

// Call Memex_Model_LoginsTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Memex_NotificationCenterTest::main") {
    Memex_NotificationCenterTest::main();
}
