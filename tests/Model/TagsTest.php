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
            array($this->profile_1['id'],   'http://example.com/1','Example 1','These are notes for example 1','foo bar baz quux'),
            array($this->profile_2['id'], 'http://example.com/2','Example 2','These are notes for example 2','    bar baz quux'),
            array($this->profile_1['id'],   'http://example.com/3','Example 3','These are notes for example 3','foo     baz quux'),
            array($this->profile_2['id'], 'http://example.com/4','Example 4','These are notes for example 4','    bar     quux'),
            array($this->profile_1['id'],   'http://example.com/5','Example 5','These are notes for example 5','foo bar baz     '),
            array($this->profile_2['id'], 'http://example.com/6','Example 6','These are notes for example 6','        baz quux'),
            array($this->profile_1['id'],   'http://example.com/7','Example 7','These are notes for example 7','foo bar baz quux'),
            array($this->profile_2['id'], 'http://example.com/8','Example 8','These are notes for example 8','    bar     quux'),
            array($this->profile_1['id'],   'http://example.com/9','Example 9','These are notes for example 9','foo     baz quux'),
            array($this->profile_2['id'], 'http://example.com/a','Example a','These are notes for example a','    bar baz     '),
            array($this->profile_1['id'],   'http://example.com/b','Example b','These are notes for example b','foo bar baz quux'),
            array($this->profile_2['id'], 'http://example.com/c','Example c','These are notes for example c','            quux')
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
     *
     */
    public function testTagDeletionOnPostTagChange()
    {
        $this->fail('TODO');
    }

    /**
     *
     */
    public function testTagDeletionOnPostDeletion()
    {
        $this->fail('TODO');
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

}

// Call Memex_Model_TagsTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Memex_Model_TagsTest::main") {
    Memex_Model_TagsTest::main();
}
