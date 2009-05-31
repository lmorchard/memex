<?php
/**
 * Actions dealing with viewing and manipulating posts.
 *
 * @package    Memex
 * @subpackage controllers
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Post_Controller extends Local_Controller
{ 
    protected $auto_render = TRUE;

    public function __construct()
    {
        parent::__construct();

        // Accept parameter to set pagination page size.
        if (isset($_GET['set_page_size']) && is_numeric($_GET['set_page_size'])) {
            // HACK: This means of setting a cookie sucks balls.
            $_COOKIE['page_size'] = (int)$_GET['set_page_size'];
            setcookie('page_size',  $_COOKIE['page_size'], time()+60*60*24*365*5);
        }

        if (!AuthProfiles::is_logged_in()) {
            if (in_array(Router::$method, array('save', 'delete'))) {
                return url::redirect(
                    url::base() . '/login' .
                    '?jump=' . rawurlencode( '/' . url::current(TRUE) )
                );
            }
        }
    }

    /**
     * Profile home page, listing posts and etc
     */
    public function profile()
    {
        $params = $this->getParamsFromRoute(array(
            'tags'    => '',
            'is_feed' => false
        ));

        // Try to match the screen name to a profile, or bail with a 404.
        $profiles_model = new Profiles_Model();
        $profile = 
            $profiles_model->fetchByScreenName($params['screen_name']);
        if (!$profile) {
            return Event::run('system.404');
        }

        // Parse out any tags specified in the URL route.
        $tags_model = new Tags_Model();
        $tags = $tags_model->parseTags($params['tags']);

        $tag_counts = $tags_model->countByProfile($profile['id']);

        $posts_model = new Posts_Model();
        $posts_count = 
            $posts_model->countByProfileAndTags($profile['id'], $tags);

        list($start, $count) = $this->setupPagination($posts_count);

        // Fetch the posts using the route tags and pagination vars.
        $posts = $posts_model->fetchByProfileAndTags(
            $profile['id'], $tags, $start, $count
        );

        $this->view->set(array(
            'tag_counts'  => $tag_counts,
            'tags'        => $tags,
            'posts'       => $posts,
            'profile'     => $profile,
            'screen_name' => $params['screen_name']
        ));

        if ('true' == $params['is_feed']) {
            return $this->renderFeed();
        }
    }

    /**
     * Tag view action
     */
    public function tag()
    {
        $params = $this->getParamsFromRoute(array(
            'tags'    => '',
            'is_feed' => false
        ));

        $tags_model = new Tags_Model();
        $tags = $tags_model->parseTags($params['tags']);

        $posts_model = new Posts_Model();
        $posts_count = $posts_model->countByTags($tags);

        list($start, $count) = $this->setupPagination($posts_count);
        $posts = $posts_model->fetchByTags($tags, $start, $count);

        $this->view->set(array(
            'tags'    => $tags,
            'posts'   => $posts,
            'profile' => null
        ));

        if ('true' == $params['is_feed']) {
            return $this->renderFeed();
        }
    }

    /**
     * Post view action.
     */
    public function view()
    {
        $params = $this->getParamsFromRoute(array(
            'uuid' => ''
        ));

        $uuid = $params['uuid'];
        if (isset($_GET['uuid'])) {
            $uuid = $_GET['uuid'];
        } elseif (isset($_POST['uuid'])) {
            $uuid = $_POST['uuid'];
        }

        $posts_model = new Posts_Model();

        if ($uuid) {
            $post = $posts_model->fetchOneByUUID($uuid);
        }
        $this->view->set('post', $post);

        // Make sure the post exists, and belongs to the current profile
        $profile_id = AuthProfiles::get_profile('id');
        if (empty($post)) {
            return Event::run('system.404');
        } elseif ($post['profile_id'] != $profile_id && $post['visibility'] > 0) {
            // TODO: Need more work on the visibility / privacy thing.
            header('HTTP/1.1 403 Forbidden');
            exit;
        }

    }

    /**
     * Post delete action.
     */
    public function delete()
    {
        $params = $this->getParamsFromRoute(array(
            'uuid' => ''
        ));

        $url = $this->input->get('url', null);

        $uuid = $params['uuid'];
        if (isset($_GET['uuid'])) {
            $uuid = $_GET['uuid'];
        } elseif (isset($_POST['uuid'])) {
            $uuid = $_POST['uuid'];
        }

        $posts_model = new Posts_Model();

        if ($uuid) {
            $post = $posts_model->fetchOneByUUID($uuid);
        }
        $this->view->set('post', $post);

        if (!isset($_POST['cancel'])) {

            $profile_id = AuthProfiles::get_profile('id');
            $posts_model = new Posts_Model();

            // If we have a URL, try looking up existing post data.
            if ($uuid) {
                $post = $posts_model->fetchOneByUUID($uuid);
            } elseif ($url) {
                $post = $posts_model->fetchOneByUrlAndProfile($url, $profile_id);
            }

            // Make sure the post exists, and belongs to the current profile
            if (empty($post)) {
                return Event::run('system.404');
            } elseif ($post['profile_id'] != $profile_id) {
                header('HTTP/1.1 403 Forbidden'); exit;
            }
            $this->view->set('post', $post);

            // Allow pre-population from query string
            if ('post' != request::method()) {
                $_GET['uuid'] = $uuid;
                return;
            }

            // Now, try validating the POST request.
            $_POST['uuid'] = $uuid;

            // Finally, perform the deletion.
            $posts_model->deleteByUUID($uuid);
        }

        return url::redirect('people/'.AuthProfiles::get_profile('screen_name'));
    }

    /**
     * Handle saving a new bookmark, with a variety of post-save redirection 
     * options.
     */
    public function save()
    {
        $params = $this->getParamsFromRoute(array(
            'uuid' => '', 'submethod' => 'save'
        ));
        $this->view->set($params);

        $have_url = false;

        // Try getting the in-progress post's URL from query or form.
        $url = null;
        if (isset($_GET['url'])) {
            $url = $_GET['url'];
        } elseif (isset($_POST['url'])) {
            $url = $_POST['url'];
        }
        if ($url) $have_url = true;

        $this->view->set('have_url', $have_url);

        $uuid = $params['uuid'];
        if (isset($_GET['uuid'])) {
            $uuid = $_GET['uuid'];
        } elseif (isset($_POST['uuid'])) {
            $uuid = $_POST['uuid'];
        }

        if (!isset($_POST['cancel'])) {

            $profile_id  = AuthProfiles::get_profile('id');
            $posts_model = new Posts_Model();

            // If we have a URL, try looking up existing post data.
            $existing_post = null;
            if ($uuid) {
                $existing_post = $posts_model->fetchOneByUUID($uuid);
            } elseif ($url) {
                $existing_post = 
                    $posts_model->fetchOneByUrlAndProfile($url, $profile_id);
            }

            if (empty($existing_post)) {
                $existing_post = array();
            } else {
                $have_url = true;
                $this->view->set('have_url', $have_url);
                if ($existing_post['profile_id'] != $profile_id) {
                    // If the logged in profile and the post profile ID don't 
                    // match, then this is a cross-profile copy and the UUID 
                    // should be nuked to force a copy instead of update.
                    unset($existing_post['profile_id']);
                    unset($existing_post['uuid']);
                    unset($existing_post['id']);
                }
            }

            if ('post' != request::method()) {
                // For GET method, at least run the filters from the validator.
                $validator = $posts_model->getValidator(array_merge(
                    $existing_post,
                    $this->input->get()
                ));
                $validator->validate();
                $_GET = $validator->as_array();
                return;
            }

            $validator = $posts_model->getValidator(array_merge(
                $existing_post,
                $this->input->post()
            ));
            if (!$validator->validate()) {
                $_POST = $validator->as_array();
                $this->view->set(
                    'errors', $validator->errors('form_errors_post')
                );
                return;
            }

            // Now, try validating the POST request.
            $new_post_data = array_merge(
                $existing_post, 
                $validator->as_array()
            );

            $new_post_data['profile_id'] = $profile_id;

            // Finally, try saving the combination of existing and new input.
            $saved_post = $posts_model->save($new_post_data);
        }

        // The ?jump parameter indicates one of several post-save redirect 
        // options.
        $jump = $this->input->post('jump', null);
        if ($jump == 'close') {
            // jump=close should close the window, but we'll do it in a view
            $this->auto_render = FALSE;
            return View::factory('post/save_doclose')->render(true);
        } elseif ($jump == 'yes' && $url) {
            // If there's a URL and ?jump=yes, then hop on over to the original URL.
            return url::redirect($url);
        } elseif (strpos($jump, '/') === 0) {
            // This jump leads to somewhere within the site
            return url::redirect($jump);
        } else {
            return url::redirect('people/'.
                AuthProfiles::get_profile('screen_name'));
        }

    }

    /**
     * Set up common pagination elements.
     */
    private function setupPagination($total)
    {
        // Set up the count, page size, and page number parameters 
        // for paginator.
        $start = $this->input->get('start', null);
        $count = $this->input->get('count', null);

        $page_number = $this->input->get('page', 1);

        if (null!=$start || null!=$count) {
            // If the ?start or ?count parameters have been supplied, honor 
            // them instead of pagination params.
            if (null==$count) $count = 10; // TODO: Make ?count a configurable default?
            if ($count < 1) $count = 1;
            if ($count > 100) $count = 100;
            if ($start < 0 || null==$start) $start = 0;
            if ($start > $total) $start = $total;
            $page_size = $count;
        } else {
            // Otherwise, honor the ?page parameter and page_size cookie.
            $page_size = $this->input->cookie('page_size', 10);
            $start = ($page_number - 1) * $page_size;
            $count = $page_size;
        }

        $first    = 1;
        $last     = (int)($total / $page_size) + 1;
        $previous = ($page_number > 1) ? $page_number - 1 : null;
        $next     = ($page_number < $last) ? $page_number + 1 : null;

        $this->view->set(array(
            'pagination' => array(
                'first'       => $first,
                'last'        => $last,
                'previous'    => $previous,
                'next'        => $next,
                'start'       => $start,
                'count'       => $count,
                'total'       => $total,
                'page_size'   => $page_size,
                'page_number' => $page_number
            )
        ));

        return array($start, $count);
    }

    /**
     * Utility function to switch view rendering to feed template.
     */
    private function renderFeed()
    {
        $params = $this->getParamsFromRoute(array(
            'format' => 'atom'
        ));
        $format = $params['format'];
        if (!valid::alpha_numeric($format)) {
            $format = 'atom';
        }
        $this->layout = null;
        $this->view->callback = $this->input->get('callback', '');
        $this->view->set_filename('post/feed-'.strtolower($format));
    }

} 
