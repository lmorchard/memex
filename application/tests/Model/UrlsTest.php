<?php
/**
 * Test class for Memex_Model_UrlsTest.
 *
 * @group Models
 *
 * @package    Memex
 * @subpackage tests
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Memex_Model_UrlsTest extends PHPUnit_Framework_TestCase 
{
    /**
     * Runs the test methods of this class.
     *
     * @return void
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite("Memex_Model_UrlsTest");
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
        $this->model = new Memex_Model_Urls();
        $this->model->deleteAll();

        $this->posts_model = new Memex_Model_Posts();
        $this->posts_model->deleteAll();
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

    /*
    public function truncateTable()
    {
        $this->model->getTable('user')->getAdapter()->getConnection()->exec('DELETE FROM user');
    }

    public function testShouldNotAllowAddingUsersWithExistingUsername()
    {
        $id = $this->model->save(array(
            'username' => 'foo',
            'email'    => 'foo@email.com',
            'fullname' => 'Foo Bar',
            'password' => md5('foobar'),
        ));

        try {
            $test = $this->model->save(array(
                'username' => 'foo',
                'email'    => 'foo2@email.com',
                'fullname' => 'Foo Bar',
                'password' => md5('foobar'),
            ));
            $this->fail('Users with duplicate names should raise exceptions');
        } catch (Exception $e) {
            $this->assertContains('duplicate', $e->getMessage());
        }
    }

    public function testShouldNotAllowAddingUsersWithExistingEmail()
    {
        $id = $this->model->save(array(
            'username' => 'foo',
            'email'    => 'foo@email.com',
            'fullname' => 'Foo Bar',
            'password' => md5('foobar'),
        ));

        try {
            $test = $this->model->save(array(
                'username' => 'foo2',
                'email'    => 'foo@email.com',
                'fullname' => 'Foo Bar',
                'password' => md5('foobar'),
            ));
            $this->fail('Users with duplicate names should raise exceptions');
        } catch (Exception $e) {
            $this->assertContains('duplicate', $e->getMessage());
        }
    }

    public function testFetchUserShouldAllowFetchingByUsername()
    {
        $id = $this->model->save(array(
            'username' => 'foo',
            'email'    => 'foo@email.com',
            'fullname' => 'Foo Bar',
            'password' => md5('foobar'),
        ));

        $user = $this->model->findUser('foo');
        $this->assertEquals($id, $user->id);
    }

    public function testFetchUserShouldAllowFetchingByEmail()
    {
        $id = $this->model->save(array(
            'username' => 'foo',
            'email'    => 'foo@email.com',
            'fullname' => 'Foo Bar',
            'password' => md5('foobar'),
        ));

        $user = $this->model->findUser('foo@email.com');
        $this->assertEquals($id, $user->id);
    }

    public function testShouldAllowBanningUsers()
    {
        $id = $this->model->save(array(
            'username' => 'foo',
            'email'    => 'foo@email.com',
            'fullname' => 'Foo Bar',
            'password' => md5('foobar'),
        ));

        $user = $this->model->findUser('foo@email.com');

        $this->model->ban($id);

        $test = $this->model->findUser('foo@email.com');
        $this->assertNotSame($user, $test);
        $this->assertNull($test);
    }
    */
}
