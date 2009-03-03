<?php
/**
 * Test class for DelApiControllerTest.
 *
 * @group Models
 */
// class DelApiControllerTest extends Zend_Test_PHPUnit_ControllerTestCase //PHPUnit_Framework_TestCase 
class DelApiControllerTest extends PHPUnit_Framework_TestCase 
{
    /**
     * Runs the test methods of this class.
     *
     * @return void
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite("DelApiControllerTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        $this->logins_model = new Logins_Model();
        $this->logins_model->deleteAll();

        $this->profiles_model = new Profiles_Model();
        $this->profiles_model->deleteAll();

        $this->urls_model = new Urls_Model();
        $this->urls_model->deleteAll();

        $this->posts_model = new Posts_Model();
        $this->posts_model->deleteAll();

        $this->tags_model = new Tags_Model();
        $this->tags_model->deleteAll();

        $this->login_data = array(
            array(
                'login_name'  => 'tester1',
                'password'    => 'first_p4$$w0rd!',
                'email'       => 'tess@test.com',
                'screen_name' => 'tester1_screenname',
                'full_name'   => 'Tess T. Erone',
                'bio'         => 'I live!'
            ),
            array(
                'login_name'  => 'tester2',
                'password'    => 'second_p4$$w0rd!',
                'email'       => 'joe@test.com',
                'screen_name' => 'tester2_screenname',
                'full_name'   => 'Joe E. Blo',
                'bio'         => 'I exist!'
            )
        );

        $this->logins   = array();
        $this->profiles = array();
        
        for ($i=0; $i<count($this->login_data); $i++) {
            $this->logins[$i] = $this->logins_model->registerWithProfile(
                $this->login_data[$i]
            );
            $this->profiles[$i] = $this->profiles_model->fetchByScreenName(
                $this->login_data[$i]['screen_name']
            );
        }

        $posts_keys = array('url', 'title', 'notes', 'tags', 'user_date');
        $posts_data = array(
            array('http://example.com/1','Example 1','These are notes for example 1','foo bar baz quux','2007-10-24T10:10:24+00:00'),
            array('http://example.com/7','Example 7','These are notes for example 7','foo bar baz quux','2007-10-25T22:10:24+00:00'),
            array('http://example.com/a','Example a','These are notes for example a','    bar baz     ','2007-10-26T03:10:24+00:00'),
            array('http://example.com/4','Example 4','These are notes for example 4','    bar     quux','2007-10-24T14:13:24+00:00'),
            array('http://example.com/5','Example 5','These are notes for example 5','foo bar baz     ','2007-10-25T20:10:24+00:00'),
            array('http://example.com/6','Example 6','These are notes for example 6','        baz quux','2007-10-25T21:10:24+00:00'),
            array('http://example.com/b','Example b','These are notes for example b','foo bar baz quux','2007-10-27T10:10:24+00:00'),
            array('http://example.com/3','Example 3','These are notes for example 3','foo     baz quux','2007-10-24T13:12:24+00:00'),
            array('http://example.com/8','Example 8','These are notes for example 8','    bar     quux','2007-10-26T01:10:24+00:00'),
            array('http://example.com/2','Example 2','These are notes for example 2','    bar baz quux','2007-10-24T12:11:24+00:00'),
            array('http://example.com/9','Example 9','These are notes for example 9','foo     baz quux','2007-10-26T02:10:24+00:00'),
            array('http://example.com/c','Example c','These are notes for example c','            quux','2007-10-27T11:10:24+00:00')
        );
        $this->test_posts = array();
        foreach ($posts_data as $post_flat) {
            $this->test_posts[] = array_merge(
                array_combine($posts_keys, $post_flat)
            );
        }

        $this->sorted_test_posts = $this->test_posts;
        usort($this->sorted_test_posts, create_function(
            '$b,$a', 
            '$b=$b["user_date"]; $a=$a["user_date"];' .
            'return ($a==$b) ? 0 : ( ($a<$b) ? -1 : 1 );'
        ));

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

        parent::setUp();
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
     * Make sure that an unknown login can't access the API.
     */
    public function testUnauthorized()
    {
        $this->performApiCall('posts/update', 'unauthorized', array(
            'login_name' => 'dunno',
            'password'   => 'whothisis'
        ), array(), null);
    }

    /**
     * Ensure that the posts/add call rejects incomplete or invalid data.
     */
    public function testPostsAddShouldRequireValidData()
    {
        $login = $this->login_data[0];
        $profile = $this->profiles[0];
        $post = $this->test_posts[0];

        $this->performApiCall(
            'posts/add', 'posts-add', $login, 
            array(
            ),
            '<result code="something went wrong" />'
        );

        $this->performApiCall(
            'posts/add', 'posts-add', $login, 
            array(
                'url' => 'this is not a URL'
            ),
            '<result code="something went wrong" />'
        );

        $this->performApiCall(
            'posts/add', 'posts-add', $login, 
            array(
                'url' => $post['url']
            ),
            '<result code="something went wrong" />'
        );

        $this->performApiCall(
            'posts/add', 'posts-add', $login, 
            array(
                'url'         => $post['url'],
                'description' => $post['title']
            ),
            '<result code="done" />'
        );

    }

    /**
     * Ensure that when ?replace=no is in effect, that an existing post is not 
     * overwritten.
     */
    public function testPostsAddShouldNotReplace()
    {
        $login = $this->login_data[0];
        $profile = $this->profiles[0];
        $post = $this->test_posts[0];

        $this->performApiCall(
            'posts/add', 'posts-add', $login, 
            array(
                'url'         => $post['url'],
                'description' => $post['title'],
                'extended'    => $post['notes'],
                'tags'        => $post['tags']
            ),
            '<result code="done" />'
        );

        $fetched_post = $this->posts_model->fetchOneByUrlAndProfile(
            $post['url'], $profile['id']
        );
        $this->assertTrue(null != $fetched_post);
        $this->assertEquals($post['title'], $fetched_post['title']);

        $this->performApiCall(
            'posts/add', 'posts-add', $login, 
            array(
                'url'         => $post['url'],
                'description' => $post['title'],
                'replace'     => 'no'
            ),
            '<result code="something went wrong" />'
        );

    }

    /**
     * Make changes to a post and ensure that the signature changes with each 
     * change
     */
    public function testPostSignature()
    {
        $login = $this->login_data[0];
        $post  = $this->test_posts[0];

        // Add the initial post
        $params = array(
            'url'         => $post['url'],
            'description' => $post['title'],
            'extended'    => $post['notes'],
            'tags'        => $post['tags']
        );
        $this->performApiCall(
            'posts/add', 'posts-add', $login, $params
        );
        $hash = md5($post['url']);

        $new_params = array(
            'description' => 'CHANGED TITLE',
            'extended'    => 'CHANGED NOTES',
            'tags'        => 'ALPHA BETA GAMMA',
            'dt'          => date('c')
        );

        // Gradually change the post and ensure that the signature changes at 
        // each step with respect to all previous signatures.
        $sigs = array();
        foreach ($new_params as $name => $value) {

            // Fetch post and assert a non-empty signature, add it to the list 
            // of known previous signatures.
            $doc = $this->performApiCall(
                'posts/get', 'posts-get', $login,
                array( 'hash' => $hash ), null
            );
            $sig1 = (string)$doc->post['meta'];
            $this->assertNotEquals($sig1, '');
            $sigs[] = $sig1;

            // Change another part of the post.
            $params = array_merge($params, array($name => $value));
            $this->performApiCall('posts/add', 'posts-add', $login, $params);

            // Fetch the post and assert that the signature has changed with 
            // respect to the previous.
            $doc = $this->performApiCall(
                'posts/get', 'posts-get', $login,
                array( 'hash' => $hash ), null
            );
            $sig2 = (string)$doc->post['meta'];
            $this->assertNotEquals($sig2, $sig1);
            $this->assertFalse(in_array($sig2, $sigs));

        }

    }

    /**
     * Ensure posts/update returns the date of the latest modification.
     */
    public function testPostsUpdate()
    {
        $login = $this->login_data[0];
        $profile = $this->profiles[0];

        $last_update = '0000-00-00T00:00:00+00:00';

        // Run this 3 times, to be sure to get initial inserts as well as 
        // updates to existing posts.
        for ($i=0; $i<3; $i++) {

            foreach ($this->test_posts as $post) {

                // Save a post.
                $this->performApiCall(
                    'posts/add', 'posts-add', $login, 
                    array(
                        'url'         => $post['url'],
                        'description' => $post['title'],
                        'extended'    => $post['notes'],
                        'tags'        => $post['tags'],
                        'dt'          => $post['user_date']
                    )
                );

                // Get the update time.
                $doc = $this->performApiCall(
                    'posts/update', 'posts-update', $login, 
                    array(), null
                );

                // Ensure the current update time is later than the 
                // last known.
                $this->assertTrue(
                    strtotime($last_update) < strtotime((string)$doc['time'])
                );

                // Wait a second, then remember the last update time before 
                // doing it all again
                sleep(1);
                $last_update = (string)$doc['time'];

            }

        }

    }

    /**
     * Ensure that counts and dates per tag work properly.
     */
    public function testDates()
    {

        // Perform all the tests for each of the known profiles.
        for ($i=0; $i<count($this->login_data); $i++) {
            $login = $this->login_data[$i];
            $profile = $this->profiles[$i];

            // Add all the test posts while keeping track of expected dates.
            $tag_dates = array();
            foreach ($this->test_posts as $post) {
                
                // Count this post by tag and date.
                $date = substr($post['user_date'], 0, 10);
                $tags = array_merge(
                    array('__ALL__'), // HACK: a 'tag' representing all.
                    $this->tags_model->parseTags($post['tags'])
                );
                foreach ($tags as $tag) {
                    // Kind of awkward, but ensure two-dimensional tag and date 
                    // array of post counts.
                    if (empty($tag_dates[$tag])) {
                        $tag_dates[$tag] = array();
                    }
                    if (empty($tag_dates[$tag][$date])) {
                        $tag_dates[$tag][$date] = 1;
                    } else {
                        $tag_dates[$tag][$date] += 1;
                    }
                }

                // Add this post using the API.
                $this->performApiCall(
                    'posts/add', 'posts-add', $login, 
                    array(
                        'url'         => $post['url'],
                        'description' => $post['title'],
                        'extended'    => $post['notes'],
                        'tags'        => $post['tags'],
                        'dt'          => $post['user_date']
                    )
                );

            }

            foreach ($tag_dates as $tag=>$test_dates) {

                // HACK: a 'tag' representing all.
                if ('__ALL__' == $tag) $tag = '';

                // Look up dates for tag.
                $doc = $this->performApiCall(
                    'posts/dates', 'posts-dates', $login, 
                    ($tag) ? array('tag'=>$tag) : array(),
                    null
                );

                // Verify the user name for the dates.
                $this->assertEquals(
                    $profile['screen_name'], 
                    (string)$doc['user']
                );

                // Verify the tags for the dates.
                $this->assertEquals(
                    $this->tags_model->parseTags($tag), 
                    $this->tags_model->parseTags((string)$doc['tag'])
                );

                // Get and sort the test dates, ensure the count of 
                // fetched dates.
                $dates = array_keys($test_dates);
                sort($dates);
                $this->assertEquals(count($dates), count($doc->date));

                // Verify all the dates and counts
                for ($i=0; $i<count($dates); $i++) {
                    $test_date  = $dates[$i];
                    $test_count = $test_dates[$test_date];

                    $this->assertEquals(
                        $test_date, (string)$doc->date[$i]['date']
                    );
                    $this->assertEquals(
                        $test_count, (string)$doc->date[$i]['count']
                    );

                    // Also try verifying post count when using 
                    // posts/get by date and tag.
                    $posts_doc = $this->performApiCall(
                        'posts/get', 'posts-get', $login, array(
                            'dt'  => $test_date,
                            'tag' => $tag
                        ), null
                    );
                    $this->assertEquals($test_count, count($posts_doc->post));

                }

            }

        }

    }

    /**
     * Try adding and verifying a bunch of posts.
     */
    public function testPostsAddGetDelete()
    {
        // Perform all the tests for each of the known profiles.
        for ($i=0; $i<count($this->login_data); $i++) {
            $login = $this->login_data[$i];
            $profile = $this->profiles[$i];

            $this->addTestPosts($login);

            // Ensure the correct number of posts exist in the model.
            $posts = $this->posts_model->fetchByProfileAndTags(
                $profile['id'], null, null, null
            );
            $this->assertEquals(count($this->test_posts), count($posts));

            // Now, try fetching all of the posts via API and verify contents.
            $cnt = 0;
            foreach ($this->test_posts as $post) {

                // Alternate using hash and URL to fetch posts.
                if ( (($cnt++) % 2) == 0) {
                    $params = array( 'hash' => md5($post['url']) );
                } else {
                    $params = array( 'url' => $post['url'] );
                }
                $doc = $this->performApiCall(
                    'posts/get', 'posts-get', $login, $params, null
                );

                // First, there should be a well-formed doc in response.
                $this->assertTrue(null != $doc);

                // Now, run down the known fields and verify them
                $this->assertEquals(
                    $profile['screen_name'], 
                    (string)$doc['user']
                );
                $this->assertPostData($post, $doc->post);

            }

            // Attempt to fetch a set of posts with a list of hashes.
            $hashes = array();
            foreach ($this->test_posts as $post) {
                $hashes[] = md5($post['url']);
            }
            $doc = $this->performApiCall(
                'posts/get', 'posts-get', $login, array(
                    'hashes' => join(' ', $hashes)
                ), null
            );
            $this->assertEquals(count($hashes), count($doc->post));

            // Verify the posts data in hash order.
            for ($i=0; $i<count($hashes); $i++) {
                $this->assertPostData($this->test_posts[$i], $doc->post[$i]);
            }

            // Try deleting all the posts, alternating between URL and hash.
            $cnt = 0;
            foreach ($this->test_posts as $post) {
                if ( (($cnt++) % 2) == 0) {
                    $params = array( 'url' => $post['url'] );
                } else {
                    $params = array( 'hash' => md5($post['url']) );
                }
                $this->performApiCall(
                    'posts/delete', 'posts-delete', $login, $params
                );
            }

            // Ensure there are zero posts.
            $posts = $this->posts_model->fetchByProfileAndTags(
                $profile['id'], null, null, null
            );
            $this->assertEquals(0, count($posts));

            // For good measure, try deleting all posts again, but assert that 
            // each deletion is an error.
            $cnt = 0;
            foreach ($this->test_posts as $post) {
                if ( (($cnt++) % 2) == 0) {
                    $params = array( 'hash' => md5($post['url']) );
                } else {
                    $params = array( 'url' => $post['url'] );
                }
                $this->performApiCall(
                    'posts/delete', 'posts-delete', $login, $params, 
                    '<result code="something went wrong" />'
                );
            }

        }

    }

    /**
     * Ensure that posts/recent works for counts.
     */
    public function testPostsRecent()
    {
        // Perform all the tests for each of the known profiles.
        for ($i=0; $i<count($this->login_data); $i++) {
            $login = $this->login_data[$i];
            $profile = $this->profiles[$i];

            $this->addTestPosts($login);

            for ($i=1; $i<count($this->sorted_test_posts); $i++) {
                $doc = $this->performApiCall(
                    'posts/recent', 'posts-recent', $login,
                    array( 'count' => $i ), null
                );
                $this->assertEquals($i, count($doc->post));
                $this->assertPostList(
                    array_slice($this->sorted_test_posts,0, $i), 
                    $doc
                );
            }
        }
    }

    /**
     * Try out the change hashes manifest
     */
    public function testPostsAllHashes()
    {
        for ($i=0; $i<count($this->login_data); $i++) {
            $login = $this->login_data[$i];
            $profile = $this->profiles[$i];

            $this->addTestPosts($login);

            $sigs = array();
            foreach ($this->sorted_test_posts as $post) {
                $hash = md5($post['url']);
                $doc = $this->performApiCall(
                    'posts/get', 'posts-get', $login,
                    array( 'hash' => $hash ), null
                );
                $sigs[] = array(
                    $hash, (string)$doc->post['meta']
                );
            }

            $hashes_doc = $this->performApiCall(
                'posts/all', 'posts-all', $login,
                array( 'hashes' => 1 ), null
            );
            
            for ($i=0; $i<count($hashes_doc->post); $i++) {
                $sig = $sigs[$i];
                $ele = $hashes_doc->post[$i];

                $this->assertEquals($sig[0], (string)$ele['url']);
                $this->assertEquals($sig[1], (string)$ele['meta']);
            }

        }
    }

    /**
     * Ensure start/results parameters for posts/all works.
     */
    public function testPostsAllPagination()
    {
        // Perform all the tests for each of the known profiles.
        for ($i=0; $i<count($this->login_data); $i++) {
            $login = $this->login_data[$i];
            $profile = $this->profiles[$i];

            $this->addTestPosts($login);

            // Try every combination of start/results pagination values for the 
            // test set.
            $total = count($this->sorted_test_posts);
            for ($start=0; $start<$total; $start++) {
                for ($results=1; $results<($total-$start); $results++) {

                    // Look up the posts for this start/results pair.
                    $doc = $this->performApiCall(
                        'posts/all', 'posts-all', $login, array( 
                            'start'   => $start,
                            'results' => $results
                        ), null
                    );

                    // Assert the correct count and set of posts.
                    $this->assertEquals($results, count($doc->post));
                    $this->assertPostList(
                        array_slice($this->sorted_test_posts, $start, $results), 
                        $doc
                    );

                }
            }
        }
    }

    /**
     * Ensure that pagination by date range works.
     */
    public function testPostsAllDateRanges()
    {
        // Perform all the tests for each of the known profiles.
        for ($i=0; $i<count($this->login_data); $i++) {
            $login = $this->login_data[$i];
            $profile = $this->profiles[$i];

            $this->addTestPosts($login);

            // Try every combination of start/results pagination values for the 
            // test set.
            $total = count($this->sorted_test_posts);
            for ($start=0; $start<$total; $start++) {
                for ($results=1; $results<($total-$start); $results++) {

                    $page = array_slice($this->sorted_test_posts, $start, $results);
                    $end_time   = $page[0]['user_date'];
                    $start_time = $page[count($page)-1]['user_date'];

                    // Look up the posts for this start/results pair.
                    $doc = $this->performApiCall(
                        'posts/all', 'posts-all', $login, array( 
                            'fromdt' => $start_time,
                            'todt'   => $end_time
                        ), null
                    );

                    $this->assertEquals($results, count($doc->post));
                    $this->assertPostList($page, $doc);
                }
            }
        }
    }

    /**
     *
     */
    public function testTagsAll()
    {
        // Perform all the tests for each of the known profiles.
        for ($i=0; $i<count($this->login_data); $i++) {
            $login = $this->login_data[$i];
            $profile = $this->profiles[$i];

            $this->addTestPosts($login);

            $tag_counts = array();
            foreach ($this->test_posts as $post) {
                $tags = $this->tags_model->parseTags($post['tags']);
                foreach ($tags as $tag) {
                    if (!isset($tag_counts[$tag]))
                        $tag_counts[$tag] = 1;
                    else
                        $tag_counts[$tag]++;
                }
            }

            foreach(array('get','all') as $path) {
                $doc = $this->performApiCall(
                    'tags/' . $path, 'tags-all', $login, array(), null
                );

                foreach ($doc->tag as $ele) {
                    $this->assertEquals(
                        $tag_counts[(string)$ele['tag']],
                        (string)$ele['count']
                    );
                }
            }

        }
    }

    /**
     *
     */
    public function testTagsDelete()
    {
        $this->fail('TODO');
    }

    /**
     *
     */
    public function testTagsRename()
    {
        $this->fail('TODO');
    }

    /**
     *
     */
    public function testTagsBundlesAll()
    {
        $this->fail('TODO');
    }

    /**
     *
     */
    public function testTagsBundlesSet()
    {
        $this->fail('TODO');
    }

    /**
     *
     */
    public function testTagsBundlesDelete()
    {
        $this->fail('TODO');
    }

    /**
     * Add the whole set of test posts via API calls.
     *
     * @param array Login details for API calls.
     */
    private function addTestPosts($login)
    {
        foreach ($this->test_posts as $post) {
            $this->performApiCall(
                'posts/add', 'posts-add', $login, 
                array(
                    'url'         => $post['url'],
                    'description' => $post['title'],
                    'extended'    => $post['notes'],
                    'tags'        => $post['tags'],
                    'dt'          => $post['user_date']
                )
            );
        }
    }

    /**
     *
     */
    private function assertPostList($posts, $ele)
    {
        for ($i=0; $i<count($posts); $i++) {
            $this->assertPostData($posts[$i], $ele->post[$i]);
        }
    }

    /**
     * Compare an array of post data against a SimpleXML element.
     */
    private function assertPostData($data, $ele)
    {
        $this->assertEquals(
            $data['title'], 
            (string)$ele['description']
        );
        $this->assertEquals(
            $data['notes'], 
            (string)$ele['extended']
        );
        $this->assertEquals(
            // Be a bit less strict here and account for time zone 
            // variance & etc.  We only care about the parsed date 
            // value
            gmdate('c', strtotime($data['user_date'])), 
            gmdate('c', strtotime((string)$ele['time']))
        );
        $this->assertEquals(
            $this->tags_model->parseTags($data['tags']), 
            $this->tags_model->parseTags((string)$ele['tag'])
        );
        $this->assertEquals(
            $data['url'], 
            (string)$ele['href']
        );
    }

    /**
     * Perform an API call using test response and request.
     */
    private function performApiCall($path, $action, $login, $params, $result='<result code="done" />')
    {
        // Build the API URL from the base, path, and query params.
        $url = Kohana::config('tests.api_v1_base_url') . '/' . $path . '?' . 
            http_build_query($params);

        // Attempt making an authenticated fetch against v1 del API
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_USERAGENT      => 'Memex/0.1',
            CURLOPT_FAILONERROR    => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERPWD        => $login['login_name'].':'.$login['password']
        ));
        $resp = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        // If the fetch wasn't successful, assume the username/password 
        // was wrong.
        //if (200 != $info['http_code']) {
        //    throw new Exception('delicious API call failed');
        //} 

        if (null != $result) {
            $this->assertEquals($result, $resp);
        }

        $doc = simplexml_load_string($resp);
        return $doc;
    }

}
