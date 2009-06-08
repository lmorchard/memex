<?php
/**
 * Test class for ProfilesTest
 *
 * @group Models
 *
 * @package    auth_profiles
 * @group      auth_profiles
 * @subpackage tests
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class ProfilesTest extends PHPUnit_Framework_TestCase 
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        DecafbadUtils_EnvConfig::apply('testing');

        $this->model = new Profiles_Model();
        $this->model->delete_all();
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
     * Ensure a login can be created and found by login name.
     */
    public function testCreateFetchUpdate()
    {
        $profile = $this->model->create(array(
            'screen_name' => 'tester1_screenname',
            'full_name'   => 'Tess T. Erone',
            'bio'         => 'I live!'
        ));

        $found_profile = $this->model->find_by_screen_name('tester1_screenname');

        $this->assertEquals($found_profile['screen_name'], 'tester1_screenname');
        $this->assertEquals($found_profile['full_name'], 'Tess T. Erone');
        $this->assertEquals($found_profile['bio'], 'I live!');

        $updated_profile = $this->model->update(array(
            'id'          => $found_profile['id'],
            'screen_name' => 'updated_tester1_screenname',
            'full_name'   => 'Updated Tess T. Erone',
            'bio'         => 'Updated I live!'
        ));

        $updated_profile = $this->model->find_by_screen_name('updated_tester1_screenname');

        $this->assertEquals($updated_profile['screen_name'], 'updated_tester1_screenname');
        $this->assertEquals($updated_profile['full_name'], 'Updated Tess T. Erone');
        $this->assertEquals($updated_profile['bio'], 'Updated I live!');

        $updated_profile_1 = $this->model->find_by_id($found_profile['id']);

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

        $this->model->set_attribute($profile['id'], 'test1', 'value1');
        $this->model->set_attribute($profile['id'], 'test2', 'value2');
        $this->model->set_attribute($profile['id'], 'test3', 'value3');

        $this->model->set_attributes($profile['id'], array(
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
            $result_value = $this->model->get_attribute($profile['id'], $name);
            $this->assertEquals($test_value, $result_value);
        }

        $result_attribs = $this->model->get_attributes($profile['id']);
        $this->assertEquals($result_attribs, $test_attribs);

        $result_attribs2 = $this->model->get_attributes($profile['id'], array(
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

        $this->model->set_attributes($profile['id'], $test_attribs3);

        $result_attribs3 = $this->model->get_attributes($profile['id']);

        $this->assertEquals($result_attribs3, $test_attribs3);

    }


}
