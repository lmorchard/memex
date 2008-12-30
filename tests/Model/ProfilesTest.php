<?php
// Call Memex_Model_ProfilesTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "Memex_Model_ProfilesTest::main");
}

require_once dirname(__FILE__) . '/../TestHelper.php';

/** Model_User */
require_once 'Profiles.php';

/**
 * Test class for Memex_Model_ProfilesTest
 *
 * @group Models
 */
class Memex_Model_ProfilesTest extends PHPUnit_Framework_TestCase 
{
    /**
     * Runs the test methods of this class.
     *
     * @return void
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite("Memex_Model_ProfilesTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        $this->model = new Memex_Model_Profiles();
        $this->model->deleteAll();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    public function tearDown()
    {
    }

    /**
     * Ensure that required fields for a login are enforced.
     */
    public function testCreateRequiredFields()
    {
        try {
            $test = $this->model->create(array());
            $this->fail('missing fields should not be allowed');
        } catch (Exception $e) {
            $this->assertContains('required', $e->getMessage());
        }
        try {
            $test = $this->model->create(array(
                'screen_name' => 'tester1'
            ));
            $this->fail('missing fields should not be allowed');
        } catch (Exception $e) {
            $this->assertContains('required', $e->getMessage());
        }
        $test = $this->model->create(array(
            'screen_name' => 'tester1',
            'full_name'   => 'Tess T. Erone'
        ));
    }

    /**
     * Ensure a login can be created and fetched by login name.
     */
    public function testCreateAndFetch()
    {
        $profile = $this->model->create(array(
            'screen_name' => 'tester1_screenname',
            'full_name'   => 'Tess T. Erone',
            'bio'         => 'I live!'
        ));

        $profile = $this->model->fetchByScreenName('tester1_screenname');

        $this->assertEquals($profile['screen_name'], 'tester1_screenname');
        $this->assertEquals($profile['full_name'], 'Tess T. Erone');
        $this->assertEquals($profile['bio'], 'I live!');
    }

    /**
     * Ensure that logins with the same login names cannot be created.
     */
    public function testShouldNotAllowDuplicateName()
    {
        $profile = $this->model->create(array(
            'screen_name' => 'tester1',
            'full_name'   => 'Tess T. Erone',
            'bio'         => 'I live!'
        ));

        try {
            $profile2 = $this->model->create(array(
                'screen_name' => 'tester1',
                'full_name'   => 'Tess T. Erone',
                'bio'         => 'I live!'
            ));
            $this->fail('duplicate screen names should raise exceptions');
        } catch (Exception $e) {
            $this->assertContains('duplicate', $e->getMessage());
        }
    }

}

// Call Memex_Model_ProfilesTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Memex_Model_ProfilesTest::main") {
    Memex_Model_ProfilesTest::main();
}
