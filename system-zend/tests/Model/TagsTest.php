<?php
// Call Memex_Model_TagsTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "Memex_Model_TagsTest::main");
}

require_once dirname(__FILE__) . '/../TestHelper.php';

/** Model_User */
require_once 'Tags.php';

/**
 * Test class for Memex_Model_TagsTest.
 *
 * @group Models
 */
class Memex_Model_TagsTest extends PHPUnit_Framework_TestCase 
{
    /**
     * Runs the test methods of this class.
     *
     * @return void
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite("Memex_Model_TagsTest");
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
        $this->model = new Memex_Model_Tags();
        $this->model->deleteAll();

        $this->posts_model = new Memex_Model_Posts();
        $this->posts_model->deleteAll();

        $this->profiles_model = new Memex_Model_Profiles();
        $this->profiles_model->deleteAll();

        $this->urls_model = new Memex_Model_Urls();
        $this->urls_model->deleteAll();

        $this->profile_1 = $this->profiles_model->create(array(
            'screen_name' => 'tester1_screenname',
            'full_name'   => 'Tess T. Erone',
            'bio'         => 'I live!'
        ));
        $this->profile_2 = $this->profiles_model->create(array(
            'screen_name' => 'tester2_screenname',
            'full_name'   => 'Joe T. Erone',
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
            array($this->profile_2['id'], 'http://example.com/c','Example c','These are notes for example c','            quux'),
            array($this->profile_1['id'], 'http://example.com/d','Example c','These are notes for example c','foo         quux')
        );
        $this->test_posts = array();
        foreach ($posts_data as $post_flat) {
            $this->test_posts[] = array_merge(
                array_combine($posts_keys, $post_flat)
            );
        }
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
     * Ensure that tag counts work.
     */
    public function testTagCounts()
    {
        $test_tag_counts = array();

        foreach ($this->test_posts as $post_data) {

            // Count and index posts by tag.
            $tags = $this->model->parseTags($post_data['tags']);
            foreach ($tags as $tag) {
                $key = $post_data['profile_id'] . ':' . $tag;
                if (!isset($test_tag_counts[$key])) {
                    $test_tag_counts[$key] = 1;
                } else {
                    $test_tag_counts[$key]++;
                }
            }

            // Save this test post.
            $this->posts_model->save($post_data);
        }

        foreach (array($this->profile_1['id'], $this->profile_2['id']) as $profile_id) {
            $test_counts = array();

            foreach ($test_tag_counts as $key=>$value) {
                list($pid, $tag) = explode(':', $key);
                if ($pid == $profile_id) {
                    $test_counts[] = array(
                        'tag'   => $tag,
                        'count' => $value
                    );
                }
            }
            usort($test_counts, array($this, '_sortByCount'));

            $result_counts = $this->model->countByProfile($profile_id);

            $this->assertEquals($test_counts, $result_counts);
        }

    }

    private function _sortByCount($a, $b)
    {
        $ac = $a["count"]; 
        $bc = $b["count"]; 
        return ($ac==$bc) ? 0 : ( ($ac>$bc) ? -1 : 1 );
    }

    /**
     * Ensure fetching by tag works.
     */
    public function testFetchByTagAndProfile()
    {
        $tags_counts = array();
        $tags_posts  = array();

        foreach ($this->test_posts as $post_data) {

            // Count and index posts by tag.
            $tags = $this->model->parseTags($post_data['tags']);
            foreach ($tags as $tag) {
                if (!isset($tags_counts[$tag])) {
                    $tags_counts[$tag] = 1;
                } else {
                    $tags_counts[$tag]++;
                }
                if (!isset($tags_posts[$tag])) {
                    $tags_posts[$tag] = array( $post_data );
                } else {
                    $tags_posts[$tag][] = array( $post_data );
                }
            }

            // Save this test post.
            $this->posts_model->save(array_merge(
                $post_data, 
                array('profile_id' => $this->profile_1['id'])
            ));
        }

        foreach ($tags_counts as $tag=>$test_count) {
            $tag_data = $this->model->fetchByTagAndProfile($tag, $this->profile_1['id']);
            $this->assertEquals($tag, $tag_data['tag']);
            $this->assertEquals($this->profile_1['id'], $tag_data['profile_id']);
            $this->assertTrue(null != $tag_data['id']);
        }

    }

    /**
     * Ensure that changes in tags on existing posts is reflected in tag 
     * counts.
     */
    public function testTagDeletionOnPostTagChange()
    {
        $p1 = $this->posts_model->save(array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/tagchange1.html',
            'title'      => 'Tag Change 1',
            'tags'       => 'foo bar baz xyzzy'
        ));
        $p2 = $this->posts_model->save(array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/tagchange2.html',
            'title'      => 'Tag Change 2',
            'tags'       => '    bar baz xyzzy'
        ));
        $p3 = $this->posts_model->save(array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/tagchange3.html',
            'title'      => 'Tag Change 3',
            'tags'       => '    bar baz'
        ));
        $p4 = $this->posts_model->save(array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/tagchange4.html',
            'title'      => 'Tag Change 4',
            'tags'       => '        baz'
        ));

        $test_counts_1 = array(
            array( 'tag' => 'baz',   'count' => '4'),
            array( 'tag' => 'bar',   'count' => '3'),
            array( 'tag' => 'xyzzy', 'count' => '2'),
            array( 'tag' => 'foo',   'count' => '1')
        );
        $result_counts_1 = 
            $this->model->countByProfile($this->profile_1['id']);
        $this->assertEquals($test_counts_1, $result_counts_1);

        $p1_new = $this->posts_model->save(array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/tagchange1.html',
            'title'      => 'Tag Change 1',
            'tags'       => 'not-foo not-bar baz not-xyzzy'
        ));

        $test_counts_2 = array(
            array( 'tag' => 'baz',       'count' => '4'),
            array( 'tag' => 'bar',       'count' => '2'),
            array( 'tag' => 'not-bar',   'count' => '1'),
            array( 'tag' => 'not-foo',   'count' => '1'),
            array( 'tag' => 'not-xyzzy', 'count' => '1'),
            array( 'tag' => 'xyzzy',     'count' => '1')
        );
        $result_counts_2 = 
            $this->model->countByProfile($this->profile_1['id']);
        $this->assertEquals($test_counts_2, $result_counts_2);

        $p2_new = $this->posts_model->save(array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/tagchange2.html',
            'title'      => 'Tag Change 2',
            'tags'       => '    not-bar not-baz not-xyzzy'
        ));
        
        $test_counts_3 = array(
            array( 'tag' => 'baz',       'count' => '3'),
            array( 'tag' => 'not-bar',   'count' => '2'),
            array( 'tag' => 'not-xyzzy', 'count' => '2'),
            array( 'tag' => 'bar',       'count' => '1'),
            array( 'tag' => 'not-baz',   'count' => '1'),
            array( 'tag' => 'not-foo',   'count' => '1')
        );
        $result_counts_3 = 
            $this->model->countByProfile($this->profile_1['id']);

        $this->assertEquals($test_counts_3, $result_counts_3);

        $p3_new = $this->posts_model->save(array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/tagchange3.html',
            'title'      => 'Tag Change 3',
            'tags'       => '    not-bar not-baz'
        ));

        $test_counts_4 = array(
            array( 'tag' => 'not-bar',   'count' => '3'),
            array( 'tag' => 'baz',       'count' => '2'),
            array( 'tag' => 'not-baz',   'count' => '2'),
            array( 'tag' => 'not-xyzzy', 'count' => '2'),
            array( 'tag' => 'not-foo',   'count' => '1')
        );
        $result_counts_4 = 
            $this->model->countByProfile($this->profile_1['id']);

        $this->assertEquals($test_counts_4, $result_counts_4);

        $p4_new = $this->posts_model->save(array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/tagchange4.html',
            'title'      => 'Tag Change 4',
            'tags'       => '        not-baz'
        ));

        $test_counts_4 = array(
            array( 'tag' => 'not-bar',   'count' => '3'),
            array( 'tag' => 'not-baz',   'count' => '3'),
            array( 'tag' => 'not-xyzzy', 'count' => '2'),
            array( 'tag' => 'baz',       'count' => '1'),
            array( 'tag' => 'not-foo',   'count' => '1')
        );
        $result_counts_4 = 
            $this->model->countByProfile($this->profile_1['id']);

        $this->assertEquals($test_counts_4, $result_counts_4);

    }

    /**
     * Ensure that deleting posts results in decremented tag counts as tag 
     * usage drops.
     */
    public function testTagDeletionOnPostDeletion()
    {
        $p1 = $this->posts_model->save(array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/tagchange1.html',
            'title'      => 'Tag Change 1',
            'tags'       => 'foo bar baz xyzzy'
        ));
        $p2 = $this->posts_model->save(array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/tagchange2.html',
            'title'      => 'Tag Change 2',
            'tags'       => '    bar baz xyzzy'
        ));
        $p3 = $this->posts_model->save(array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/tagchange3.html',
            'title'      => 'Tag Change 3',
            'tags'       => '    bar baz'
        ));
        $p4 = $this->posts_model->save(array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/tagchange4.html',
            'title'      => 'Tag Change 4',
            'tags'       => '        baz'
        ));

        $test_counts_1 = array(
            array( 'tag' => 'baz',   'count' => '4'),
            array( 'tag' => 'bar',   'count' => '3'),
            array( 'tag' => 'xyzzy', 'count' => '2'),
            array( 'tag' => 'foo',   'count' => '1')
        );
        $result_counts_1 = 
            $this->model->countByProfile($this->profile_1['id']);
        $this->assertEquals($test_counts_1, $result_counts_1);

        $this->posts_model->deleteById($p1['id']);

        $test_counts_2 = array(
            array( 'tag' => 'baz',   'count' => '3'),
            array( 'tag' => 'bar',   'count' => '2'),
            array( 'tag' => 'xyzzy', 'count' => '1')
        );
        $result_counts_2 = 
            $this->model->countByProfile($this->profile_1['id']);
        $this->assertEquals($test_counts_2, $result_counts_2);

        $this->posts_model->deleteById($p2['id']);

        $test_counts_3 = array(
            array( 'tag' => 'baz',   'count' => '2'),
            array( 'tag' => 'bar',   'count' => '1')
        );
        $result_counts_3 = 
            $this->model->countByProfile($this->profile_1['id']);
        $this->assertEquals($test_counts_3, $result_counts_3);

        $this->posts_model->deleteById($p3['id']);

        $test_counts_3 = array(
            array( 'tag' => 'baz',   'count' => '1')
        );
        $result_counts_3 = 
            $this->model->countByProfile($this->profile_1['id']);
        $this->assertEquals($test_counts_3, $result_counts_3);

        $this->posts_model->deleteById($p4['id']);

        $test_counts_4 = array(
        );
        $result_counts_4 = 
            $this->model->countByProfile($this->profile_1['id']);
        $this->assertEquals($test_counts_4, $result_counts_4);
    }

    /**
     * Ensure that tag position in individual records changes when tags are 
     * changed on a single post.
     */
    public function testTagPositionUpdateOnTagChange()
    {
        $p1 = $this->posts_model->save(array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/tagchange1.html',
            'title'      => 'Tag Change 1',
            'tags'       => 'foo bar baz xyzzy'
        ));
    
        $test_tags_1 = array('foo', 'bar', 'baz', 'xyzzy');
        $result_tags_data_1 = $this->model->fetchByPost($p1['id']);
        $result_tags_1 = array();
        foreach ($result_tags_data_1 as $tags_data) {
            $this->assertEquals($tags_data['post_id'], $p1['id']);
            $result_tags_1[] = $tags_data['tag'];
        }
        $this->assertEquals($test_tags_1, $result_tags_1);

        $p1_new1 = $this->posts_model->save(array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/tagchange1.html',
            'title'      => 'Tag Change 1',
            'tags'       => 'xyzzy foo bar baz'
        ));
    
        $test_tags_2 = array('xyzzy', 'foo', 'bar', 'baz');
        $result_tags_data_2 = $this->model->fetchByPost($p1_new1['id']);
        $result_tags_2 = array();
        foreach ($result_tags_data_2 as $tags_data) {
            $this->assertEquals($tags_data['post_id'], $p1_new1['id']);
            $result_tags_2[] = $tags_data['tag'];
        }
        $this->assertEquals($test_tags_2, $result_tags_2);

        $p1_new2 = $this->posts_model->save(array(
            'profile_id' => $this->profile_1['id'],
            'url'        => 'http://example.com/tagchange1.html',
            'title'      => 'Tag Change 1',
            'tags'       => 'xyzzy bar foo baz'
        ));
    
        $test_tags_3 = array('xyzzy', 'bar', 'foo', 'baz');
        $result_tags_data_3 = $this->model->fetchByPost($p1_new2['id']);
        $result_tags_3 = array();
        foreach ($result_tags_data_3 as $tags_data) {
            $this->assertEquals($tags_data['post_id'], $p1_new2['id']);
            $result_tags_3[] = $tags_data['tag'];
        }
        $this->assertEquals($test_tags_3, $result_tags_3);

    }

}

// Call Memex_Model_TagsTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Memex_Model_TagsTest::main") {
    Memex_Model_TagsTest::main();
}
