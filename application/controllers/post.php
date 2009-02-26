<?php
/**
 * Actions dealing with viewing and manipulating posts.
 */
class Post_Controller extends Controller 
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

        if (!$this->auth->isLoggedIn()) {
            if (in_array(Router::$method, array('save', 'delete'))) {
                return url::redirect(
                    url::base() . '/login' .
                    '?jump=' . rawurlencode( url::current(TRUE) )
                );
            }
        }
    }

    /**
     * Profile home page, listing posts and etc
     */
    public function profile()
    {
        $args = Router::$arguments;
        $screen_name = 
            count($args) ? array_shift($args) : null;
        $tags = 
            count($args) ? urldecode(join('/',$args)) : '';
        $is_feed = false;

        // Try to match the screen name to a profile, or bail with a 404.
        $profiles_model = new Profiles_Model();
        $profile        = $profiles_model->fetchByScreenName($screen_name);
        if (!$profile) {
            return Event::run('system.404');
        }

        // Parse out any tags specified in the URL route.
        $tags_model = new Tags_Model();
        $tags = $tags_model->parseTags($tags);

        $tag_counts = $tags_model->countByProfile($profile['id']);

        $posts_model = new Posts_Model();
        $posts_count = 
            $posts_model->countByProfileAndTags($profile['id'], $tags);

        list($start, $count) = $this->setupPagination($posts_count);

        // Fetch the posts using the route tags and pagination vars.
        $posts = $posts_model->fetchByProfileAndTags(
            $profile['id'], $tags, $start, $count
        );

        $this->setViewData(array(
            'tag_counts'  => $tag_counts,
            'tags'        => $tags,
            'posts'       => $posts,
            'profile'     => $profile,
            'screen_name' => $screen_name
        ));

        //if ($is_feed) {
        //    return $this->renderFeed();
        //}
    }

    /**
     * Tag view action
     */
    public function tag()
    {
        $is_feed = False;

        $args = Router::$arguments;
        if (0 == count($args)) {
            $tags = '';
        } else {
            $tags = urldecode(join('/', $args));
        }

        $tags_model = new Tags_Model();
        $tags = $tags_model->parseTags($tags);

        $posts_model = new Posts_Model();
        $posts_count = $posts_model->countByTags($tags);

        list($start, $count) = $this->setupPagination($posts_count);
        $posts = $posts_model->fetchByTags($tags, $start, $count);

        $this->setViewData(array(
            'tags'    => $tags,
            'posts'   => $posts,
            'profile' => null
        ));

        if ($is_feed) {
            return $this->renderFeed();
        }
    }

    /**
     * Post view action.
     */
    public function view()
    {
        $identity  = Zend_Auth::getInstance()->getIdentity();
        $request   = $this->getRequest();
        $get_data  = $this->input->get();
        $post_data = $request->getPost();

        $uuid = $request->getParam('uuid');
        if (isset($get_data['uuid'])) {
            $uuid = $get_data['uuid'];
        } elseif (isset($post_data['uuid'])) {
            $uuid = $post_data['uuid'];
        }

        $posts_model = $this->_helper->getModel('Posts');

        if ($uuid) {
            $post = $posts_model->fetchOneByUUID($uuid);
        }
        $this->view->post = $post;

        // Make sure the post exists, and belongs to the current profile
        $profile_id = (!$identity) ? null : $identity->default_profile['id'];
        if (empty($post)) {
            throw new Zend_Exception("Post '$uuid' not found.", 404);
        } elseif ($post['profile_id'] != $profile_id && $post['visibility'] > 0) {
            // TODO: Need more work on the visibility / privacy thing.
            throw new Zend_Exception("View of '$uuid' forbidden.", 403);
        }

    }

    /**
     * Post delete action.
     */
    public function delete()
    {
        $identity  = Zend_Auth::getInstance()->getIdentity();
        $request   = $this->getRequest();
        $get_data  = $this->input->get();
        $post_data = $request->getPost();

        $uuid = $request->getParam('uuid');
        if (isset($get_data['uuid'])) {
            $uuid = $get_data['uuid'];
        } elseif (isset($post_data['uuid'])) {
            $uuid = $post_data['uuid'];
        }

        if (!isset($post_data['cancel'])) {

            $profile_id  = $identity->default_profile['id'];
            $posts_model = $this->_helper->getModel('Posts');

            $form = $this->view->delete_form = $this->_helper->getForm(
                'postDelete', array('action'  => $this->view->url())
            );

            // If we have a URL, try looking up existing post data.
            if ($uuid) {
                $post = $posts_model->fetchOneByUUID($uuid);
            } elseif ($url) {
                $post = $posts_model->fetchOneByUrlAndProfile($url, $profile_id);
            }
            $this->view->post = $post;

            // Make sure the post exists, and belongs to the current profile
            if (empty($post)) {
                throw new Zend_Exception("Post '$uuid' not found.", 404);
            } elseif ($post['profile_id'] != $profile_id) {
                throw new Zend_Exception("Delete of '$uuid' forbidden.", 403);
            }

            // Allow pre-population from query string
            if (!$this->getRequest()->isPost()) {
                $get_data['uuid'] = $uuid;
                $form->isValid($get_data);
                return;
            }

            // Now, try validating the POST request.
            $post_data['uuid'] = $uuid;
            if (!$form->isValid($post_data)) {
                return;
            }

            // Finally, perform the deletion.
            $posts_model->deleteByUUID($uuid);
        }

        // Any other values for ?jump lead to the profile page.
        return $this->_helper->redirector->gotoRoute(
            array('screen_name' => $identity->default_profile['screen_name']),
            'post_profile'
        );

    }

    /**
     * Handle saving a new bookmark, with a variety of post-save redirection 
     * options.
     */
    public function save()
    {
        $identity  = Zend_Auth::getInstance()->getIdentity();
        $request   = $this->getRequest();
        $get_data  = $this->input->get();
        $post_data = $request->getPost();

        $have_url = false;

        // Try getting the in-progress post's URL from query or form.
        $url = null;
        if (isset($get_data['url'])) {
            $url = $get_data['url'];
        } elseif (isset($post_data['url'])) {
            $url = $post_data['url'];
        }
        if ($url) $have_url = true;

        $uuid = $request->getParam('uuid');
        if (isset($get_data['uuid'])) {
            $uuid = $get_data['uuid'];
        } elseif (isset($post_data['uuid'])) {
            $uuid = $post_data['uuid'];
        }

        if (!isset($post_data['cancel'])) {

            $profile_id  = $identity->default_profile['id'];
            $posts_model = $this->_helper->getModel('Posts');

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
                if ($existing_post['profile_id'] != $profile_id) {
                    // If the logged in profile and the post profile ID don't 
                    // match, then this is a cross-profile copy and the UUID 
                    // should be nuked to force a copy instead of update.
                    unset($existing_post['uuid']);
                    unset($existing_post['id']);
                }
            }

            $form = $this->view->post_form = $this->_helper->getForm(
                'post', array(
                    'action'  => $this->view->url(),
                    'have_url' => $have_url
                )
            );

            // Allow pre-population from query string
            if (!$this->getRequest()->isPost()) {
                $new_post_data = array_merge($existing_post, $get_data);
                $form->populate($new_post_data);
                return;
            }

            // Now, try validating the POST request.
            $new_post_data = array_merge($existing_post, $post_data);
            if (!$form->isValid($new_post_data)) {
                return;
            }
            $new_post_data = $form->getValues();

            $new_post_data['profile_id'] = $profile_id;

            // Finally, try saving the combination of existing and new input.
            $saved_post = $posts_model->save($new_post_data);
        }

        // The ?jump parameter indicates one of several post-save redirect 
        // options.
        $jump = $post_data['jump'];
        if ($jump == 'close') {

            // jump=close should close the window, but we'll do it in a view script.
            return $this->renderScript('post/save_doclose.phtml');

        } elseif ($jump == 'yes' && $url) {
            
            // If there's a URL and ?jump=yes, then hop on over to the original URL.
            return $this->_helper->redirector->gotoUrl($url);

        } elseif (strpos($jump, '/') === 0) {
            
            // This jump leads to somewhere within the site
            return $this->_helper->redirector->gotoUrl($jump, array(
                'prependBase' => true
            ));

        } else {

            // Any other values for ?jump lead to the profile page.
            return $this->_helper->redirector->gotoRoute(
                array('screen_name' => $identity->default_profile['screen_name']),
                'post_profile'
            );

        }

    }

    /**
     * Set up common pagination elements.
     */
    private function setupPagination($posts_count)
    {
        $this->setViewData('posts_count', $posts_count);

        // Set up the count, page size, and page number parameters 
        // for paginator.
        $start = $this->input->get('start', null);
        $count = $this->input->get('count', null);

        if (null!=$start || null!=$count) {
            // If the ?start or ?count parameters have been supplied, honor 
            // them instead of pagination params.
            if (null==$count) $count = 15; // TODO: Make ?count a configurable default?
            if ($count < 1) $count = 1;
            if ($count > 100) $count = 100;
            if ($start < 0 || null==$start) $start = 0;
            if ($start > $posts_count) $start = $posts_count;
            
            $page_size = $count;
            $page_number = round($start / $count);
        } else {
            // Otherwise, honor the ?page parameter and page_size cookie.
            $page_size = $this->input->cookie('page_size', 10);
            $page_number = $this->input->get('page', 1);
            
            $start = ($page_number - 1) * $page_size;
            $count = $page_size;
        }

        $this->setViewData(array(
            'start'       => $start,
            'count'       => $count,
            'page_size'   => $page_size,
            'page_number' => $page_number
        ));

        /*
        // Build the paginator for the view.
        $paginator = new Zend_Paginator(
            new Zend_Paginator_Adapter_Null($posts_count)
        );
        $this->view->paginator = $paginator
            ->setCurrentPageNumber($page_number)
            ->setItemCountPerPage($page_size);
         */

        return array($start, $count);
    }

    /**
     * Utility function to switch view rendering to feed template.
     */
    private function renderFeed()
    {
        $request = $this->getRequest();
        $action  = $request->getActionName();
        $format  = $request->getParam('format');

        $alnum = new Zend_Validate_Alnum();
        if (!$alnum->isValid($format)) {
            $format = 'atom';
        }

        $this->view->callback = 
            $this->input->get('callback', '');

        return $this->render('feed'.ucfirst($format));
    }

} 
