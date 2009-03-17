<?php
/**
 * Actions to support delicious.com v1 API
 * see: http://delicious.com/help/api
 */
class Delicious_Api_Controller extends Local_Controller
{ 
    protected $auto_render = FALSE;

    /**
     * Enforce HTTP basic auth for all API actions.
     */
    public function __construct()
    {
        parent::__construct();

        header('Content-Type: text/xml');

        // This looks like an infinite loop, but it'll be escaped via break on 
        // error or return on success.
        while(1) {

            if (!isset($_SERVER['PHP_AUTH_USER'])) 
                break;

            $logins_model = new Logins_Model();
            $login = $logins_model->fetchByLoginName($_SERVER['PHP_AUTH_USER']);

            if ($login['password'] != md5($_SERVER['PHP_AUTH_PW'])) {
                break;
            }

            $this->profile = 
                $logins_model->fetchDefaultProfileForLogin($login['id']);

            // Auth success!
            return;
        }

        header('WWW-Authenticate: Basic realm="memex del v1 API"');
        header('HTTP/1.0 401 Unauthorized');
        exit;
    }

    /**
     *  Returns the last update time for the user, as well as the number of new 
     *  items in the user's inbox since it was last visited.
     *
     *  Use this before calling posts/all to see if the data has changed since 
     *  the last fetch.
     */
    public function posts_update()
    {
        $posts_model = new Posts_Model();
        
        $last_update = $posts_model->fetchLastModifiedDateByProfile(
            $this->profile['id']
        );

        $x = new Memex_XmlWriter();
        $x->update(array(
            'time' => $last_update,
            'inboxnew' => 0
        ));
        echo $x->getXML();
    }

    /**
     *  Returns one or more posts on a single day matching the arguments. If no 
     *  date or url is given, most recent date will be used.
     *
     *  &tag={TAG}+{TAG}+...+{TAG}
     *      (optional) Filter by this tag.
     *  &dt={CCYY-MM-DDThh:mm:ssZ}
     *      (optional) Filter by this date, defaults to the most recent date on 
     *      which bookmarks were saved.
     *  &url={URL}
     *      (optional) Fetch a bookmark for this URL, regardless of date. Note: 
     *      Be sure to URL-encode the argument value.
     *  &hash={HASH}
     *      (optional) Fetch a bookmark for this URL MD5 hash
     *  &hashes={MD5}+{MD5}+...+{MD5}
     *      (optional) Fetch multiple bookmarks by one or more URL MD5s 
     *      regardless of date, separated by URL-encoded spaces (ie. '+').
     */
    public function posts_get()
    {
        $posts_model = new Posts_Model();

        $params = $_GET;

        if (!empty($params['url'])) {

            // Fetch a single post by URL.
            return $this->renderPosts(array( 
                $posts_model->fetchOneByUrlAndProfile(
                    $params['url'], $this->profile['id']
                )
            ));

        } else if (!empty($params['hash'])) {

            // Fetch a single post by hash.
            return $this->renderPosts(array(
                $posts_model->fetchOneByHashAndProfile(
                    $params['hash'], $this->profile['id']
                )
            ));

        } else if (!empty($params['hashes'])) {

            // Fetch a set of posts by hashes
            $hashes = explode(' ', $params['hashes']);
            return $this->renderPosts(
                $posts_model->fetchByHashesAndProfile(
                    $hashes, $this->profile['id']
                )
            );

        }
        
        // Come up with a start/end date range for a day, today or whatever 
        // specified.
        $date = (!empty($params['dt'])) ? 
            $params['dt'] : date('Y-m-d');
        $start_date = $date . "T00:00:00-00:00";
        $end_date   = $date . "T23:59:59-00:00";

        $tags_model = new Tags_Model();
        $tags = $tags_model->parseTags($this->input->get('tag', ''));

        $posts = $posts_model->fetchBy(
            null, null, null, $this->profile['id'], 
            $tags, $start_date, $end_date, 0, null, 
            'user_date desc'
        );

        $this->renderPosts($posts, $tags, $date);
    }

    /**
     *  Returns a list of the most recent posts, filtered by argument. Maximum 100.
     *
     *  &tag={TAG}
     *      (optional) Filter by this tag.
     *  &count={1..100}
     *      (optional) Number of items to retrieve (Default:15, Maximum:100). 
     */
    public function posts_recent()
    {
        $posts_model = new Posts_Model();

        $tags_model = new Tags_Model();
        $tags = $tags_model->parseTags($this->input->get('tag', ''));

        $count = $this->input->get('count', 15);
        if ($count < 1) $count = 1;
        if ($count > 100) $count = 100;

        $posts = $posts_model->fetchBy(
            null, null, null, $this->profile['id'], 
            $tags, null, null, 0, $count, 
            'user_date desc'
        );

        $this->renderPosts($posts, $tags);
    }

    /**
     *  Returns all posts.
     *
     *  &tag={TAG}
     *      (optional) Filter by this tag.
     *  &start={#}
     *      (optional) Start returning posts this many results into the set.
     *  &results={#}
     *      (optional) Return this many results.
     *  &fromdt={CCYY-MM-DDThh:mm:ssZ}
     *      (optional) Filter for posts on this date or later
     *  &todt={CCYY-MM-DDThh:mm:ssZ}
     *      (optional) Filter for posts on this date or earlier
     */
    public function posts_all()
    {
        $params  = $_GET;

        $posts_model = new Posts_Model();

        if ($this->input->get('hashes', false) !== false) {
            // If ?hashes parameter sent, switch to send hash manifest.

            $hashes = $posts_model->fetchHashesByProfile($this->profile['id']);

            $x = new Memex_XmlWriter(array(
                'parents' => array('posts')
            ));
            $x->posts();
            foreach ($hashes as $hash) {
                $x->post(array(
                    'meta' => $hash['signature'],
                    'url'  => $hash['hash']
                ));
            }
            $x->pop();
            echo $x->getXML();

        } else {
            // Otherwise, use supplied criteria to look up posts.

            $tags_model = new Tags_Model();
            $tags = $tags_model->parseTags($this->input->get('tag', ''));

            $start = (int)$this->input->get('start', 0);
            if ($start < 0) $start = 0;

            $results = $this->input->get('results', null);

            $start_date = !empty($params['fromdt']) ?
                date('c', strtotime($params['fromdt'])) : null;

            $end_date = !empty($params['todt']) ?
                date('c', strtotime($params['todt'])) : null;

            $posts = $posts_model->fetchBy(
                null, null, null, 
                $this->profile['id'], 
                $tags, 
                $start_date, $end_date, 
                $start, $results, 
                'user_date desc'
            );

            $last_update = $posts_model->fetchLastModifiedDateByProfile(
                $this->profile['id']
            );
            $posts_count = $posts_model->countByProfileAndTags(
                $this->profile['id'], $tags
            );

            $this->renderPosts($posts, $tags, null, $last_update, 
                $start, $results, $posts_count);

        }

    }

    /**
     *  Returns a list of dates with the number of posts at each date.
     *
     *  &tag={TAG}
     *      (optional) Filter by this tag
     */
    public function posts_dates()
    {
        $tags_model = new Tags_Model();
        $tags = $tags_model->parseTags($this->input->get('tag', ''));
        
        $x = new Memex_XmlWriter(array('parents' => array('dates')));
        $x->dates(array(
            'user' => $this->profile['screen_name'],
            'tag'  => $tags_model->concatenateTags($tags)
        ));

        $posts_model = new Posts_Model();
        $dates = $posts_model->fetchDatesByTagsAndProfile(
            $tags, $this->profile['id']
        );

        foreach ($dates as $row) {
            $x->date(array(
                'count' => $row['count'],
                'date'  => $row['date']
            ));
        }

        $x->pop();
        echo $x->getXML();
    }

    /**
     *  Add a post
     *
     *  &url={URL}
     *      (required) the url of the item.
     *  &description={...}
     *      (required) the description of the item.
     *  &extended={...}
     *      (optional) notes for the item.
     *  &tags={...}
     *      (optional) tags for the item (space delimited).
     *  &dt={CCYY-MM-DDThh:mm:ssZ}
     *      (optional) datestamp of the item (format "CCYY-MM-DDThh:mm:ssZ"). 
     *      Requires a LITERAL "T" and "Z" like in ISO8601 at 
     *      http://www.cl.cam.ac.uk/~mgk25/iso-time.html for example: 
     *      "1984-09-01T14:21:31Z"
     *  &replace=no
     *      (optional) don't replace post if given url has already been posted.
     *  &shared=no
     *      (optional) make the item private
     */
    public function posts_add()
    {
        $posts_model = new Posts_Model();

        $new_post_data = array(
            'url'       => $this->input->get('url', null),
            'title'     => $this->input->get('description', null),
            'notes'     => $this->input->get('extended', null),
            'tags'      => $this->input->get('tags', null),
            'user_date' => $this->input->get('dt', null)
        );

        if ($this->input->get('replace', 'yes') == 'no') {
            $fetched_post = $posts_model->fetchOneByUrlAndProfile(
                $new_post_data['url'], $this->profile['id']
            );
            if (null != $fetched_post) {
                return $this->renderError();
            }
        }

        // Use the post form to validate the incoming API data.
        $validator = $posts_model->getValidator($new_post_data);
        if (!$validator->validate()) {
            return $this->renderError();
        }

        // Normalize date input as ISO8601
        if (null != $new_post_data['user_date']) {
            $new_post_data['user_date'] = 
                gmdate('c', strtotime($new_post_data['user_date']));
        }

        $new_post_data['profile_id'] = $this->profile['id'];

        $posts_model->save($new_post_data);

        return $this->renderSuccess();
    }

    /**
     *  Delete a post
     *
     *  &url={URL}
     *      (optional) the url of the item.
     *  &hash={HASH}
     *      (optional) the URL MD5 of the item.
     */
    public function posts_delete()
    {
        $params = $_GET;

        $posts_model = new Posts_Model();

        if (!empty($params['url'])) {
            $post = $posts_model->fetchOneByUrlAndProfile(
                $params['url'], $this->profile['id']
            );
        } else if (!empty($params['hash'])) {
            $post = $posts_model->fetchOneByHashAndProfile(
                $params['hash'], $this->profile['id']
            );
        }

        if (empty($post)) {
            return $this->renderError();
        } else {
            $posts_model->deleteById($post['id']);
            return $this->renderSuccess();
        }
    }

    /**
     * Return a list of tags and counts for a profile.
     */
    public function tags_all()
    {
        $tags_model = new Tags_Model();
        $tags = $tags_model->countByProfile(
            $this->profile['id'], 0, null
        );

        $x = new Memex_XmlWriter(array(
            'parents' => array('tags')
        ));
        $x->tags();
        foreach ($tags as $tag) {
            $x->tag(array(
                'count' => $tag['count'],
                'tag'   => $tag['tag']
            ));
        }
        $x->pop();
        echo $x->getXML();
    }

    /**
     * Render posts as XML
     */
    private function renderPosts($posts, $tags=null, $date=null, 
        $last_update=null, $start=null, $results=null, $posts_count=null)
    {
        $tags_model = new Tags_Model();

        $x = new Memex_XmlWriter(array('parents' => array('posts')));

        $x->posts(array(
            'user'  => $this->profile['screen_name'],
            'tag'   => $tags_model->concatenateTags($tags),
            'dt'    => $date,
            'total' => $posts_count,
            'count' => $results,
            'start' => $start
        ));

        foreach ($posts as $post) {
            $x->post(array(
                'href'        => $post['url'],
                'hash'        => md5($post['url']),
                'meta'        => $post['signature'],
                'description' => $post['title'],
                'extended'    => $post['notes'],
                'tag'         => $tags_model->concatenateTags($post['tags_parsed']),
                'time'        => gmdate('c', strtotime($post['user_date']))
            ));
        }

        $x->pop();
        echo $x->getXML();
    }

    /**
     * Render an API success message.
     */
    public function renderSuccess($msg='done')
    {
        echo '<result code="'.$msg.'" />';
    }

    /**
     * Render an API error message.
     */
    public function renderError($msg='something went wrong')
    {
        echo '<result code="'.$msg.'" />';
    }

}
