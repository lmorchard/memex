<?php
/**
 * Test class for Model_User.
 *
 * @group Models
 *
 * @package    auth_profiles
 * @group      auth_profiles
 * @subpackage tests
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class LoginsTest extends PHPUnit_Framework_TestCase 
{
    /**
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        DecafbadUtils_EnvConfig::apply('testing');

        $this->model = new Logins_Model();
        $this->model->delete_all();

        $this->profiles_model = new Profiles_Model();
        $this->profiles_model->delete_all();
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

        $login = $this->model->fetch_by_login_name('tester1');

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
        $login = $this->model->register_with_profile(array(
            'login_name'  => 'tester1',
            'email'       => 'tester1@example.com',
            'password'    => 'tester_password',
            'screen_name' => 'tester1_screenname',
            'full_name'   => 'Tess T. Erone',
            'bio'         => 'I live!'
        ));
        $this->assertTrue(null !== $login);

        $profile = $this->profiles_model->fetch_by_screen_name('tester1_screenname');

        $this->assertTrue(null !== $profile);
        $this->assertEquals($profile['screen_name'], 'tester1_screenname');
        $this->assertEquals($profile['full_name'], 'Tess T. Erone');
        $this->assertEquals($profile['bio'], 'I live!');

        $default_profile = 
            $this->model->fetch_default_profile_for_login($login['id']);
        $this->assertEquals($profile['id'], $default_profile['id']);
    }

    /**
     * Since login and profile creation during registration are two steps,
     * ensure that a failed profile creation doesn't result in a deadend login.
     */
    public function testFailedRegistrationShouldNotCreateLogin()
    {
        try {
            $login_id = $this->model->register_with_profile(array(
                'login_name' => 'tester1',
                'email'      => 'tester1@example.com',
                'password'   => 'tester_password',
            ));
            $this->fail('Missing profile details should cause registration to fail');
        } catch (Exception $e) {
            $this->assertContains('required', $e->getMessage());
            $login = $this->model->fetch_by_login_name('tester1');
            $this->assertNull($login);
        }
    }
    
}
