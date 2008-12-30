<?php
/**
 * Actions dealing with viewing and manipulating posts.
 */
class PostController extends Zend_Controller_Action  
{ 

    public function preDispatch()
    {
        if (Zend_Auth::getInstance()->hasIdentity()) {
        } else {
            if (in_array($this->getRequest()->getActionName(), array('save', 'delete'))) {
                $this->_helper->redirector->gotoRoute(array(), 'auth_login');
            }
        }
    }

    /**
     * Profile home page, listing posts and etc
     */
    public function profileAction()
    {
        $request = $this->getRequest();

        // Try to match the screen name to a profile, or bail with a 404.
        $profiles_model = $this->_helper->getModel('Profiles');
        $screen_name    = $request->getParam('screen_name');
        $profile        = $profiles_model->fetchByScreenName($screen_name);
        if (!$profile) {
            throw new Zend_Exception("Profile '$screen_name' not found.", 404);
        }
        $this->view->profile = $profile;
        $this->view->screen_name = $screen_name;

        // Parse out any tags specified in the URL route.
        $tags_model = $this->_helper->getModel('Tags');
        $tags = $this->view->tags = 
            $tags_model->parseTags($request->getParam('tags'));

        $tag_counts = $this->view->tag_counts = 
            $tags_model->countByProfile($profile['id']);

        // Set up the count, page size, and page number parameters 
        // for paginator.
        $posts_model = $this->_helper->getModel('Posts');
        $posts_count = $this->view->posts_count =
            $posts_model->countByProfileAndTags($profile['id'], $tags);
        $page_size   = 25;
        $page_number = $request->getQuery('page');
        if (!$page_number) $page_number = 1;

        // Build the paginator for the view.
        $paginator = new Zend_Paginator(
            new Zend_Paginator_Adapter_Null($posts_count)
        );
        $this->view->paginator = $paginator
            ->setCurrentPageNumber($page_number)
            ->setItemCountPerPage($page_size);

        // Fetch the posts using the route tags and pagination vars.
        $posts_start = ($page_number - 1) * $page_size;
        $posts = $posts_model->fetchByProfileAndTags(
            $profile['id'], $tags, $posts_start, $page_size
        );
        $this->view->posts = $posts;
    }

    /**
     * Tag view action
     */
    function tagAction()
    {
        $request = $this->getRequest();

        // Parse out any tags specified in the URL route.
        $tags_model = $this->_helper->getModel('Tags');
        $tags = $this->view->tags = 
            $tags_model->parseTags($request->getParam('tags'));

        // Set up the count, page size, and page number parameters 
        // for paginator.
        $posts_model = $this->_helper->getModel('Posts');
        $posts_count = $this->view->posts_count =
            $posts_model->countByTags($tags);
        $page_size   = 25;
        $page_number = $request->getQuery('page');
        if (!$page_number) $page_number = 1;

        // Build the paginator for the view.
        $paginator = new Zend_Paginator(
            new Zend_Paginator_Adapter_Null($posts_count)
        );
        $this->view->paginator = $paginator
            ->setCurrentPageNumber($page_number)
            ->setItemCountPerPage($page_size);

        // Fetch the posts using the route tags and pagination vars.
        $posts_start = ($page_number - 1) * $page_size;
        $posts = $posts_model->fetchByTags(
            $tags, $posts_start, $page_size
        );
        $this->view->posts = $posts;
    }

    /**
     * Post view action.
     */
    function viewAction()
    {
        $identity  = Zend_Auth::getInstance()->getIdentity();
        $request   = $this->getRequest();
        $get_data  = $request->getQuery();
        $post_data = $request->getPost();

        $uuid = $request->getParam('uuid');
        if (isset($get_data['uuid'])) {
            $uuid = $get_data['uuid'];
        } elseif (isset($post_data['uuid'])) {
            $uuid = $post_data['uuid'];
        }

        $posts_model = $this->_helper->getModel('Posts');

        // If we have a URL, try looking up existing post data.
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
    function deleteAction()
    {
        $identity  = Zend_Auth::getInstance()->getIdentity();
        $request   = $this->getRequest();
        $get_data  = $request->getQuery();
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
    function saveAction()
    {
        $identity  = Zend_Auth::getInstance()->getIdentity();
        $request   = $this->getRequest();
        $get_data  = $request->getQuery();
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
                }
            }

            $form = $this->view->post_form = $this->_helper->getForm(
                'post',
                array(
                    'action'  => $this->view->url(),
                    'have_url' => $have_url
                )
            );

            // Allow pre-population from query string
            if (!$this->getRequest()->isPost()) {
                $new_post_data = array_merge($existing_post, $get_data);
                $form->isValid($new_post_data);
                return;
            }

            // Now, try validating the POST request.
            $new_post_data = array_merge($existing_post, $post_data);
            if (!$form->isValid($new_post_data)) {
                return;
            }

            $new_post_data['profile_id'] = $profile_id;

            // Finally, try saving the combination of existing and new input.
            $saved_post = $posts_model->save($new_post_data);
        }

        // The ?jump parameter indicates one of several post-save redirect 
        // options.
        $jump = $post_data['jump'];
        if ($jump == 'doclose' || $jump == 'close') {

            // jump=doclose or jump=close should close the window after 
            // posting.
            return $this->_helper->redirector->gotoRoute(
                array('controller'=>'post', 'action'=>'doclose'), 
                'post_doclose'
            );

        } elseif (strpos($jump, '/') === 0) {
            
            // jump=/... forwards the user to some path within the site
            return $this->_helper->redirector->gotoUrl($jump, array(
                'prependBase' => true
            ));

        } elseif ($jump == 'yes' && $url) {
            
            // If there's a URL and ?jump=yes, then hop on over to the original URL.
            return $this->_helper->redirector->gotoUrl($url);

        } else {

            // Any other values for ?jump lead to the profile page.
            return $this->_helper->redirector->gotoRoute(
                array('screen_name' => $identity->default_profile['screen_name']),
                'post_profile'
            );

        }

    }

} 
