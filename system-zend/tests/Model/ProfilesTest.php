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
    public function testCreateFetchUpdate()
    {
        $profile = $this->model->create(array(
            'screen_name' => 'tester1_screenname',
            'full_name'   => 'Tess T. Erone',
            'bio'         => 'I live!'
        ));

        $fetched_profile = $this->model->fetchByScreenName('tester1_screenname');

        $this->assertEquals($fetched_profile['screen_name'], 'tester1_screenname');
        $this->assertEquals($fetched_profile['full_name'], 'Tess T. Erone');
        $this->assertEquals($fetched_profile['bio'], 'I live!');

        $updated_profile = $this->model->update(array(
            'id'          => $fetched_profile['id'],
            'screen_name' => 'updated_tester1_screenname',
            'full_name'   => 'Updated Tess T. Erone',
            'bio'         => 'Updated I live!'
        ));

        $updated_profile = $this->model->fetchByScreenName('updated_tester1_screenname');

        $this->assertEquals($updated_profile['screen_name'], 'updated_tester1_screenname');
        $this->assertEquals($updated_profile['full_name'], 'Updated Tess T. Erone');
        $this->assertEquals($updated_profile['bio'], 'Updated I live!');

        $updated_profile_1 = $this->model->fetchById($fetched_profile['id']);

        $this->assertEquals($updated_profile_1['screen_name'], 'updated_tester1_screenname');
        $this->assertEquals($updated_profile_1['full_name'], 'Updated Tess T. Erone');
        $this->assertEquals($updated_profile_1['bio'], 'Updated I live!');
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

    /**
     * Exercise profile attributes.
     */
    public function testProfileAttributes()
    {
        $profile = $this->model->create(array(
            'screen_name' => 'tester1_screenname',
            'full_name'   => 'Tess T. Erone',
            'bio'         => 'I live!'
        ));

        $this->model->setAttribute($profile['id'], 'test1', 'value1');
        $this->model->setAttribute($profile['id'], 'test2', 'value2');
        $this->model->setAttribute($profile['id'], 'test3', 'value3');

        $this->model->setAttributes($profile['id'], array(
            'test4' => 'value4',
            'test5' => 'value5',
            'test6' => 'value6'
        ));

        $test_attribs = array(
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3',
            'test4' => 'value4',
            'test5' => 'value5',
            'test6' => 'value6'
        );

        foreach ($test_attribs as $name=>$test_value) {
            $result_value = $this->model->getAttribute($profile['id'], $name);
            $this->assertEquals($test_value, $result_value);
        }

        $result_attribs = $this->model->getAttributes($profile['id']);
        $this->assertEquals($result_attribs, $test_attribs);

        $result_attribs2 = $this->model->getAttributes($profile['id'], array(
            'test2', 'test4', 'test6'
        ));
        $test_attribs2 = array(
            'test2' => 'value2',
            'test4' => 'value4',
            'test6' => 'value6'
        );
        $this->assertEquals($result_attribs2, $test_attribs2);

        $test_attribs3 = array(
            'test1' => 'updated_value1',
            'test2' => 'updated_value2',
            'test3' => 'updated_value3',
            'test4' => 'updated_value4',
            'test5' => 'updated_value5',
            'test6' => 'updated_value6'
        );

        $this->model->setAttributes($profile['id'], $test_attribs3);

        $result_attribs3 = $this->model->getAttributes($profile['id']);

        $this->assertEquals($result_attribs3, $test_attribs3);

    }


}

// Call Memex_Model_ProfilesTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Memex_Model_ProfilesTest::main") {
    Memex_Model_ProfilesTest::main();
}
