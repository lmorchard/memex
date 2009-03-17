<?php
/**
 * Test class for Memex_Model_PostsTest.
 *
 * @group Models
 *
 * @package    Memex
 * @subpackage tests
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class PostsTest extends PHPUnit_Framework_TestCase 
{
    /**
     * Runs the test methods of this class.
     *
     * @return void
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite("Memex_Model_PostsTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        $this->model = new Posts_Model();
        $this->model->deleteAll();

        $this->profiles_model = new Profiles_Model();
        $this->profiles_model->deleteAll();

        $this->urls_model = new Urls_Model();
        $this->urls_model->deleteAll();

        $this->tags_model = new Tags_Model();
        $this->tags_model->deleteAll();

        $this->profile_1 = $this->profiles_model->create(array(
            'screen_name' => 'tester1_screenname',
            'full_name'   => 'Tess T. Erone',
            'bio'         => 'I live!'
        ));

        $this->profile_2 = $this->profiles_model->create(array(
            'screen_name' => 'tester2_screenname',
            'full_name'   => 'Joe Tester',
            'bio'         => 'I exist!'
        ));

        $posts_keys = array('profile_id', 'url', 'title', 'notes', 'tags');
        $posts_data = array(
            array($this->profile_1['id'], 'http://example.com/1','Example 1','These are notes for example 1','foo bar baz quux'),
            array($this->profile_2['id'], 'http://example.com/2','Example 2','These are notes for example 2','    bar baz quux'),
            array($this->profile_1['id'], 'http://example.com/3','Example 3','These are notes for example 3','foo     baz quux'),
            array($this->profile_2['id'], 'http://example.com/4','Example 4','These are notes for example 4','    bar     quux'),
            array($this->profile_1['id'], 'http://example.com/5','Example 5','These are notes for example 5','foo bar baz     '),
            array($this->profile_2['id'], 'http://example.com/6','Example 6','These are notes for example 6','        baz quux'),
            array($this->profile_1['id'], 'http://example.com/7','Example 7','These are notes for example 7','foo bar baz quux'),
            array($this->profile_2['id'], 'http://example.com/8','Example 8','These are notes for example 8','    bar     quux'),
            array($this->profile_1['id'], 'http://example.com/9','Example 9','These are notes for example 9','foo     baz quux'),
            array($this->profile_2['id'], 'http://example.com/a','Example a','These are notes for example a','    bar baz     '),
            array($this->profile_1['id'], 'http://example.com/b','Example b','These are notes for example b','foo bar baz quux'),
            array($this->profile_2['id'], 'http://example.com/c','Example c','These are notes for example c','            quux')
        );
        $this->test_posts = array();
        foreach ($posts_data as $post_flat) {
            $this->test_posts[] = array_merge(
                array_combine($posts_keys, $post_flat)
            );
        }

        $this->tag_intersections = array(
            '',
            'foo',
            '    bar',
            '        baz',
            '            quux',
            'foo bar', 
            'foo     baz', 
            'foo         quux', 
            '    bar baz quux', 
            'foo bar baz quux',
            'quux baz bar foo'
        );

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
    public function testSaveRequiredFields()
    {
        try {
            $test_post = $this->model->save(array());
            $this->fail('missing required fields should not be allowed');
        } catch (Exception $e1) {
            $this->assertContains('required', $e1->getMessage());
        }
        try {
            $test_post = $this->model->save(array(
                'url' => 'http://example.com'
            ));
            $this->fail('missing required fields should not be allowed');
        } catch (Exception $e2) {
            $this->assertContains('required', $e2->getMessage());
        }
        try {
            $test_post = $this->model->save(array(
                'url'   => 'http://example.com',
                'title' => 'Example bookmark'
            ));
        } catch (Exception $e3) {
            $this->assertContains('required', $e3->getMessage());
        }
        try {
            $test_post = $this->model->save(array(
                'profile_id' => $this->profile_1['id'],
                'url'        => 'http://example.com',
                'title'      => 'Example bookmark'
            ));
        } catch (Exception $e4) {
            $this->fail('all required fields supplied, but failed anyway ' . 
                $e4->getMessage());
        }
    }

    /**
     * Ensure that valid dates are required and used in posts. 
     */
    public function testPostUserDates()
    {
        try {
            $test_post = $this->model->save(array(
                'profile_id' => $this->profile_1['id'],
                'url'        => 'http://example.com',
                'title'      => 'Example bookmark',
                'user_date'  => 'THIS IS A BOGUS DATE'
            ));
            $this->fail('only a valid date should be accepted');
        } catch (Exception $e) {
            $this->assertContains('required', $e->getMessage());
        }
        try {
            $test_post = $this->model->save(array(
                'profile_id' => $this->profile_1['id'],
                'url'        => 'http://example.com',
                'title'      => 'Example bookmark',
                'user_date'  => '2007-10-24T10:10:24-0500'
            ));
        } catch (Exception $e) {
            $this->fail('a valid ISO8601 date should be accepted');
        }
        $this->assertEquals(
            strtotime('2007-10-24T10:10:24-0500'),
            strtotime($test_post['user_date'])
        );
    }

    /**
     * Save a post and check what gets saved in the models.
     */
    public function testPostSave()
    {
        $post_data = array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com',
            'title'      => 'Test bookmark #1',
            'notes'      => 'These are test notes',
            'tags'       => 'foo bar baz quux xyzzy'
        );

        $normalized_url = 'http://example.com/';

        // There shouldn't yet be a URL entry for this bookmark.
        $url = $this->urls_model->fetchByUrl($post_data['url']);
        $this->assertFalse($url);

        // Saving the bookmark should result in data returned.
        $saved_post = $this->model->save($post_data);
        $this->assertTrue(null != $saved_post);

        // The URL in the saved bookmark should reflect normalization.
        $this->assertEquals($normalized_url, $saved_post['url']);

        // Assert equality between input and saved post data, except URL.
        foreach (array('profile_id', 'title', 'notes', 'tags') as $name)
            $this->assertEquals($post_data[$name], $saved_post[$name]);

        // Ensure that a new URL record has been created for this bookmark.
        $url = $this->urls_model->fetchByUrl($post_data['url']);
        $this->assertTrue(null != $url);
        $this->assertEquals($normalized_url, $url['url']);

        // Fetch the bookmark.
        $fetched_post = $this->model->fetchOneByUrlAndProfile(
            $post_data['url'], $this->profile_1['id']
        );
        $this->assertTrue(null != $fetched_post);

        // Assert that the input and fetched post contents match.
        $this->assertEquals($normalized_url, $fetched_post['url']);
        foreach (array('profile_id', 'title', 'notes', 'tags') as $name)
            $this->assertEquals($post_data[$name], $fetched_post[$name]);

        $this->assertEquals($fetched_post['screen_name'], 'tester1_screenname');

        // Assert that the saved and fetched post contents match.
        foreach (array('url','title','notes','tags','profile_id') as $name) {
            $this->assertEquals(
                $name.'='.$fetched_post[$name],
                $name.'='.$saved_post[$name]
            );
        }

        // Fetch the bookmark by UUID.
        $fetched_post = $this->model->fetchOneByUUID($saved_post['uuid']);
        $this->assertTrue(null != $fetched_post);

        // Assert that the input and fetched post contents match.
        $this->assertEquals($normalized_url, $fetched_post['url']);
        foreach (array('profile_id', 'title', 'notes', 'tags') as $name)
            $this->assertEquals($post_data[$name], $fetched_post[$name]);

        $this->assertEquals($fetched_post['screen_name'], 'tester1_screenname');

        // Assert that the saved and fetched post contents match.
        foreach (array('url','title','notes','tags','profile_id') as $name) {
            $this->assertEquals(
                $name.'='.$fetched_post[$name],
                $name.'='.$saved_post[$name]
            );
        }
    }

    /**
     * Ensure that post deletion by ID works.
     */
    public function testDeleteById()
    {
        // Do this a bunch of times to make sure IDs aren't colliding.
        for ($i=0; $i<10; $i++) {

            $post_data = array(
                'profile_id' => $this->profile_1['id'],
                'url'        => 'http://example.com/foobar.html',
                'title'      => 'Example bookmark',
                'user_date'  => '2007-10-24T10:10:24-0500'
            );
            $test_post = $this->model->save($post_data);

            $fetched_post = $this->model->fetchOneByUrlAndProfile(
                $post_data['url'], $this->profile_1['id']
            );
            $this->assertEquals($post_data['url'], $fetched_post['url']);

            $this->model->deleteById($test_post['id']);

            $fetched_post2 = $this->model->fetchOneByUrlAndProfile(
                $post_data['url'], $this->profile_1['id']
            );
            $this->assertNull($fetched_post2);

        }
    }

    /**
     * Ensure that post deletion by UUID works.
     */
    public function testDeleteByUUID()
    {
        // Do this a bunch of times to make sure IDs aren't colliding.
        for ($i=0; $i<10; $i++) {

            $post_data = array(
                'profile_id' => $this->profile_1['id'],
                'url'        => 'http://example.com/foobar.html',
                'title'      => 'Example bookmark',
                'user_date'  => '2007-10-24T10:10:24-0500'
            );
            $test_post = $this->model->save($post_data);

            $fetched_post = $this->model->fetchOneByUrlAndProfile(
                $post_data['url'], $this->profile_1['id']
            );
            $this->assertEquals($post_data['url'], $fetched_post['url']);
            $this->assertEquals($test_post['uuid'], $fetched_post['uuid']);

            $rv = $this->model->deleteByUUID($fetched_post['uuid']);

            $fetched_post2 = $this->model->fetchOneByUUID(
                $fetched_post['uuid']
            );
            $this->assertNull($fetched_post2);

            $fetched_post3 = $this->model->fetchOneByUrlAndProfile(
                $post_data['url'], $this->profile_1['id']
            );
            $this->assertNull($fetched_post3);

        }
    }

    /**
     * Ensure that post deletion by url and profile works.
     */
    public function testDeleteByUrlAndProfile()
    {
        // Do this a bunch of times to make sure IDs aren't colliding.
        for ($i=0; $i<10; $i++) {

            $post_data = array(
                'profile_id' => $this->profile_1['id'],
                'url'        => 'http://example.com/foobar.html',
                'title'      => 'Example bookmark',
                'user_date'  => '2007-10-24T10:10:24-0500'
            );
            $test_post = $this->model->save($post_data);

            $fetched_post = $this->model->fetchOneByUrlAndProfile(
                $post_data['url'], $this->profile_1['id']
            );
            $this->assertEquals($post_data['url'], $fetched_post['url']);

            $rv = $this->model->deleteByUrlAndProfile(
                $post_data['url'], $this->profile_1['id']
            );

            $fetched_post2 = $this->model->fetchOneByUrlAndProfile(
                $post_data['url'], $this->profile_1['id']
            );

            $this->assertNull($fetched_post2);

        }
    }

    /**
     * Ensure that changing the URL of a post doesn't result in duplicate 
     * posts.
     */
    public function testChangeUrl()
    {
        $post_data = array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/foobar.html',
            'title'      => 'Example bookmark',
            'notes'      => 'Notes for the example bookmark',
            'tags'       => 'alpha beta gamma woo',
            'user_date'  => '2007-10-24T10:10:24-0500'
        );
        
        $url_1 = $post_data['url'];
        $url_2 = 'http://example.com/barfoo/yayhooray.html';

        $saved_post_1 = $this->model->save($post_data);
        $uuid = $saved_post_1['uuid'];

        $changed_post = $saved_post_1;
        $changed_post['url'] = $url_2;

        $saved_post_2 = $this->model->save($changed_post);
        $this->assertEquals($uuid, $saved_post_2['uuid']);

        $should_be_null_post = $this->model->fetchOneByUrlAndProfile(
            $url_1, $this->profile_1['id']
        );
        $this->assertNull($should_be_null_post);
    }

    /**
     * Ensure that an accurate count can be gotten.
     */
    public function testCountByProfileAndTags()
    {
        $test_count = rand(4, 12);

        for ($i=0; $i<$test_count; $i++) {
            $this->model->save(array(
                'profile_id' => $this->profile_1['id'],
                'url'        => "http://example.com/page$i",
                'title'      => "Example bookmark #$i",
                'notes'      => "Notes for example bookmark #$i",
                'tags'       => "tag-a-$i tag-b-$i tag-c-$i"
            ));
        }

        $result_count = $this->model->countByProfile($this->profile_1['id']);
        $this->assertEquals($test_count, $result_count);
    }

    /**
     * Exercise fetch and counts indexed by tags.
     */
    public function testFetchAndCountByProfileAndTags()
    {
        $tags_counts = array();
        $tags_posts  = array();
        $url_posts   = array();

        // Process all the test posts.
        foreach ($this->test_posts as $post_data) {

            // Index this post by URL.
            $url = $post_data['url'];
            $url_posts[$url] = $post_data;

            // Compare tags against intersections.
            $tags = $this->tags_model->parseTags($post_data['tags']);
            foreach ($this->tag_intersections as $i) {
                $i_tags = $this->tags_model->parseTags($i);
                if (count(array_intersect($tags, $i_tags)) == count($i_tags)) {

                    // Count this post by tag intersection.
                    if (!isset($tags_counts[$i])) {
                        $tags_counts[$i] = 1;
                    } else {
                        $tags_counts[$i]++;
                    }

                    // Index this post by tag intersection.
                    if (!isset($tags_posts[$i])) {
                        $tags_posts[$i] = array( $url => $post_data );
                    } else {
                        $tags_posts[$i][$url] = $post_data;
                    }

                }
            }

            // Save this test post.
            $this->model->save(array_merge(
                $post_data, array('profile_id' => $this->profile_1['id'])
            ));
        }

        // Run through all the defined test intersections and test model data.
        foreach ($tags_counts as $tags_str=>$test_count) {
            $tags = $this->tags_model->parseTags($tags_str);

            // Ensure the count for this intersection is correct.
            $result_count = 
                $this->model->countByProfileAndTags($this->profile_1['id'], $tags);
            $this->assertEquals($test_count, $result_count);

            // Ensure the count for this intersection is correct, by fetching 
            // actual data.
            $result_posts = $this->model->fetchByProfileAndTags(
                $this->profile_1['id'], $tags, null, null
            );
            $this->assertEquals($test_count, count($result_posts));

            // Ensure the content for each of the fetched posts is correct.
            foreach ($result_posts as $result_post) {
                
                // Double check that the profile screen name is present.
                $this->assertEquals(
                    $result_post['screen_name'], 
                    'tester1_screenname'
                );

                // Ensure the post content for each field of the fetched post.
                $url = $result_post['url'];
                foreach (array('url','title','notes','tags') as $name) {
                    $this->assertEquals(
                        $name.'='.$result_post[$name], 
                        $name.'='.$tags_posts[$tags_str][$url][$name]
                    );
                }

            }
        }

    }

    /**
     * Exercise fetch and counts indexed by tags.
     *
     * HACK: This is basically a copy of the previous test, just with varying 
     * profile IDs.  Maybe refactor some day?
     */
    public function testFetchAndCountByTags()
    {
        $screen_names = array(
            $this->profile_1['id'] => $this->profile_1['screen_name'],
            $this->profile_2['id'] => $this->profile_2['screen_name']
        );

        $tags_counts = array();
        $tags_posts  = array();
        $url_posts   = array();

        // Process all the test posts.
        foreach ($this->test_posts as $post_data) {

            // Index this post by URL.
            $url = $post_data['url'];
            $url_posts[$url] = $post_data;

            // Compare tags against intersections.
            $tags = $this->tags_model->parseTags($post_data['tags']);
            foreach ($this->tag_intersections as $i) {
                $i_tags = $this->tags_model->parseTags($i);
                if (count(array_intersect($tags, $i_tags)) == count($i_tags)) {

                    // Count this post by tag intersection.
                    if (!isset($tags_counts[$i])) {
                        $tags_counts[$i] = 1;
                    } else {
                        $tags_counts[$i]++;
                    }

                    // Index this post by tag intersection.
                    if (!isset($tags_posts[$i])) {
                        $tags_posts[$i] = array( $url => $post_data );
                    } else {
                        $tags_posts[$i][$url] = $post_data;
                    }

                }
            }

            // Save this test post.
            $this->model->save($post_data);
        }

        // Run through all the defined test intersections and test model data.
        foreach ($tags_counts as $tags_str=>$test_count) {
            $tags = $this->tags_model->parseTags($tags_str);

            // Ensure the count for this intersection is correct.
            $result_count = 
                $this->model->countByTags($tags);
            $this->assertEquals($test_count, $result_count);

            // Ensure the count for this intersection is correct, by fetching 
            // actual data.
            $result_posts = $this->model->fetchByTags($tags, null, null);
            $this->assertEquals($test_count, count($result_posts));

            // Ensure the content for each of the fetched posts is correct.
            foreach ($result_posts as $result_post) {
                
                // Double check that the profile screen name is present.
                $this->assertEquals(
                    $result_post['screen_name'], 
                    $screen_names[$result_post['profile_id']]
                );

                // Ensure the post content for each field of the fetched post.
                $url = $result_post['url'];
                foreach (array('profile_id','url','title','notes','tags') as $name) {
                    $this->assertEquals(
                        $name.'='.$result_post[$name], 
                        $name.'='.$tags_posts[$tags_str][$url][$name]
                    );
                }

            }
        }

    }

}
