<?php
/**
 * Test class for Model_User.
 *
 * @group Models
 *
 * @package    Memex
 * @subpackage tests
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class LoginsTest extends PHPUnit_Framework_TestCase 
{
    /**
     * Runs the test methods of this class.
     *
     * @return void
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite("Memex_Model_LoginsTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        $this->model = new Logins_Model();
        $this->model->deleteAll();

        $this->profiles_model = new Profiles_Model();
        $this->profiles_model->deleteAll();
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
     * Ensure that required fields for a login are enforced.
     */
    public function testCreateRequiredFields()
    {
        try {
            $test_id = $this->model->create(array());
            $this->fail('Logins with missing fields should not be allowed');
        } catch (Exception $e1) {
            $this->assertContains('required', $e1->getMessage());
        }
        try {
            $test_id = $this->model->create(array(
                'login_name' => 'tester1'
            ));
            $this->fail('Logins with missing fields should not be allowed');
        } catch (Exception $e2) {
            $this->assertContains('required', $e2->getMessage());
        }
        try {
            $test_id = $this->model->create(array(
                'login_name' => 'tester1',
                'password'   => 'tester_password'
            ));
            $this->fail('Logins with missing fields should not be allowed');
        } catch (Exception $e3) {
            $this->assertContains('required', $e3->getMessage());
        }
        try {
            $test_id = $this->model->create(array(
                'login_name' => 'tester1',
                'password'   => 'tester_password',
                'email'      => 'tester1@example.com'
            ));
        } catch (Exception $e) {
            $this->fail('Users with duplicate login names should raise exceptions');
        }
    }

    /**
     * Ensure a login can be created and fetched by login name.
     */
    public function testCreateAndFetchLogin()
    {
        $login_id = $this->model->create(array(
            'login_name' => 'tester1',
            'email'      => 'tester1@example.com',
            'password'   => 'tester_password',
        ));

        $login = $this->model->fetchByLoginName('tester1');

        $this->assertEquals($login['login_name'], 'tester1');
        $this->assertEquals($login['email'], 'tester1@example.com');
        $this->assertEquals($login['password'], md5('tester_password'));
    }

    /**
     * Ensure that logins with the same login names cannot be created.
     */
    public function testShouldNotAllowDuplicateLoginName()
    {
        $login = $this->model->create(array(
            'login_name' => 'tester1',
            'email'      => 'tester1@example.com',
            'password'   => 'tester_password'
        ));

        try {
            $login2 = $this->model->create(array(
                'login_name' => 'tester1',
                'email'      => 'tester1@example.com',
                'password'   => 'tester_password'
            ));
            $this->fail('Users with duplicate login names should raise exceptions');
        } catch (Exception $e) {
            $this->assertContains('duplicate', $e->getMessage());
        }
    }
    
    /**
     * Since login and profile creation during registration are two steps,
     * ensure that a failed profile creation doesn't result in a deadend login.
     */
    public function testRegistrationShouldCreateProfile()
    {
        $login = $this->model->registerWithProfile(array(
            'login_name'  => 'tester1',
            'email'       => 'tester1@example.com',
            'password'    => 'tester_password',
            'screen_name' => 'tester1_screenname',
            'full_name'   => 'Tess T. Erone',
            'bio'         => 'I live!'
        ));
        $this->assertTrue(null !== $login);

        $profile = $this->profiles_model->fetchByScreenName('tester1_screenname');

        $this->assertTrue(null !== $profile);
        $this->assertEquals($profile['screen_name'], 'tester1_screenname');
        $this->assertEquals($profile['full_name'], 'Tess T. Erone');
        $this->assertEquals($profile['bio'], 'I live!');

        $default_profile = 
            $this->model->fetchDefaultProfileForLogin($login['id']);
        $this->assertEquals($profile['id'], $default_profile['id']);
    }

    /**
     * Since login and profile creation during registration are two steps,
     * ensure that a failed profile creation doesn't result in a deadend login.
     */
    public function testFailedRegistrationShouldNotCreateLogin()
    {
        try {
            $login_id = $this->model->registerWithProfile(array(
                'login_name' => 'tester1',
                'email'      => 'tester1@example.com',
                'password'   => 'tester_password',
            ));
            $this->fail('Missing profile details should cause registration to fail');
        } catch (Exception $e) {
            $this->assertContains('required', $e->getMessage());
            $login = $this->model->fetchByLoginName('tester1');
            $this->assertNull($login);
        }
    }
    
}
