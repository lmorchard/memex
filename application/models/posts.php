<?php
/**
 * Model managing posts
 */
class Posts_Model extends Model
{
    protected $_table_name = 'posts';

    public function buildPostSignature($data)
    {
        return md5(join('|', array(
            @$data['url'], @$data['title'], @$data['notes'], @$data['tags'], 
            @$data['user_date'], @$data['visibility']
        )));
    }

    /**
     * Save a post with the given data for the given profile, creating a new 
     * one or overwriting an existing one if necessary.
     *
     * @param array Post data to save
     * @param string Profile ID
     * @return array Post data after save
     */
    public function save($post_data)
    {
        if (empty($post_data['url']))
            throw new Exception('url required');
        if (empty($post_data['title']))
            throw new Exception('title required');
        if (empty($post_data['profile_id']))
            throw new Exception('profile_id required');
        
        if (!empty($post_data['user_date'])) {
            $date_in = strtotime($post_data['user_date'], time());
            if (!$date_in)
                throw new Exception('valid optional date required');
            $post_data['user_date'] = date('c', $date_in);
        } else {
            $post_data['user_date'] = date('c');
        }

        // Get an ID for the post's URL and set the ID in post data
        $urls_model = new Urls_Model();
        $url_data = $urls_model->fetchOrCreate(
            $post_data['url'], $post_data['profile_id']
        );
        $post_data['url_id'] = $url_data['id'];

        // Try looking up an existing post for this URL and profile.
        $row = null;
        if (!empty($post_data['id'])) {
            $row = $this->db->select()->from($this->_table_name)
                ->where('id', $post_data['id'])
                ->get()->current();
        } elseif (!empty($post_data['uuid'])) {
            $row = $this->db->select()->from($this->_table_name)
                ->where('uuid', $post_data['uuid'])
                ->get()->current();
        } else {
            $row = $this->db->select()->from($this->_table_name)
                ->where('url_id', $url_data['id'])
                ->where('profile_id', $post_data['profile_id'])
                ->get()->current();
        }

        // If there's no existing post, create a new one.
        if (false == $row) {
            $update = false;
            $row = array(
                'uuid'       => uuid::uuid(),
                'url_id'     => $url_data['id'],
                'profile_id' => $post_data['profile_id'],
                'created'    => date('c'),
                'tags'       => '',
                'notes'      => ''
            );
        } else {
            $update = true;
        }
        $row['modified'] = date('c');

        // Has the URL been changed in an existing post?
        if ($row['url_id'] != $url_data['id']) {
            // TODO: Delete URL record if last bookmark reference gone?
            // Probably a good job for an offline queue.
        }

        // Update the post's data and save it.  Note that only a select set of 
        // fields are used, which prevents changes in UUID and others
        $accepted_post_fields = array(
            'profile_id', 'url_id', 'title', 'notes', 'tags', 
            'visibility', 'user_date', 'visibility'
        );
        foreach ($accepted_post_fields as $key) {
            if (isset($post_data[$key]))
                $row[$key] = $post_data[$key];
        }
        $row['signature'] = $this->buildPostSignature($post_data);

        if ($update) {
            $this->db->update(
                $this->_table_name, 
                $row, 
                array('id' => $row['id'])
            );
        } else {
            $row['id'] = $this->db
                ->insert($this->_table_name, $row)
                ->insert_id();
        }
        
        // HACK: Re-fetch the just-saved post.  Ensures consistent data, but 
        // probably needs some work to avoid cache issues later on.
        $saved_post = $this->fetchOneById($row['id']);

        // Send out message that a post has been updated
        Event::run('Memex.model_posts.post_updated', $saved_post);

        // Return the results of the save.
        return $saved_post;
    }

    /**
     * Fetch just signatures and hashes for all posts for an account.
     *
     * @param string Profile ID
     * @return array list of signature/hash pairs
     */
    public function fetchHashesByProfile($profile_id)
    {
        $select = $this->db
            ->select('signature', 'urls.hash')
            ->from($this->_table_name)
            ->join('urls', 'urls.id=posts.url_id')
            ->where('posts.profile_id', $profile_id)
            ->orderby('user_date', 'desc');
        return $select->get()->result_array();
    }

    /**
     * Fetch the last modified date for posts for a profile.
     *
     * @param string Profile ID
     * @return string last modified date
     */
    public function fetchLastModifiedDateByProfile($profile_id)
    {
        $select = $this->db
            ->select('MAX(modified) as last_modified')
            ->from($this->_table_name)
            ->where('posts.profile_id', $profile_id);
        $row = $select->get()->current();
        return gmdate('c', strtotime($row['last_modified']));
    }

    /**
     * Collect dates and counts by tags and profile ID
     *
     * @param array Tags by which to filter
     * @param string Profile ID
     * @return array List of tags and counts
     */
    public function fetchDatesByTagsAndProfile($tags, $profile_id)
    {
        $select = $this->db
            ->select(
                'DATE_FORMAT(user_date, "%Y-%m-%d") date', 
                'count(posts.id) as count'
            )
            ->from($this->_table_name)
            ->where('posts.profile_id', $profile_id)
            ->orderby('date')
            ->groupby('date');

        $this->_addWhereForTags($select, $tags);

        return $select->get()->result_array();
    }

    /**
     * Fetch post by post ID
     *
     * @param string Post ID
     * @return array A single post
     */
    public function fetchOneById($id) 
    {
        return $this->fetchOneBy($id, null, null, null, null);
    }

    /**
     * Fetch post by post UUID
     *
     * @param string Post UUID
     * @return array A single post
     */
    public function fetchOneByUUID($uuid) 
    {
        return $this->fetchOneBy(null, null, null, $uuid, null);
    }

    /**
     * Attempt to fetch a post for the given URL and profile ID.
     *
     * @param string URL
     * @param string Profile ID
     * @return array Post data
     */
    public function fetchOneByUrlAndProfile($url, $profile_id)
    {
        return $this->fetchOneBy(null, $url, null, null, $profile_id);
    }

    /**
     * Attempt to fetch a post for the given hash and profile ID.
     *
     * @param string Hash
     * @param string Profile ID
     * @return array Post data
     */
    public function fetchOneByHashAndProfile($hash, $profile_id)
    {
        return $this->fetchOneBy(null, null, $hash, null, $profile_id);
    }

    /**
     * Fetch one post by a variety of criteria
     *
     * @param string Post ID
     * @param string Post URL
     * @param string Post UUID
     * @param string Profile ID
     * @return array A single post
     */
    public function fetchOneBy($id=null, $url=null, $hash=null, $uuid=null, $profile_id=null)
    {
        // Try looking up an existing post for this URL and profile.
        $select = $this->_getPostsSelect();

        $select->limit(1);

        if (null != $profile_id) 
            $select->where('profile_id', $profile_id);
        if (null != $id)
            $select->where('posts.id', $id);
        if (null != $uuid)
            $select->where('posts.uuid', $uuid);
        if (null != $hash)
            $select->where('urls.hash', $hash);
        if (null != $url)
            $select->where('urls.url', url::normalize($url));

        $data = $this->_postsRowSetToArray(
            $select->get()->result_array()
        );
        return empty($data) ? null : $data[0];
    }

    /**
     * Fetch posts by tags
     *
     * @param array List of tags for intersection
     * @param integer Start index
     * @param integer Count of results
     * @param string Order ({field} {asc,desc})
     * @return array Posts
     */
    public function fetchByTags($tags, $start=0, $count=10, $order='user_date desc')
    {
        return $this->fetchBy(null, null, null, null, $tags, null, null, $start, $count, $order);
    }

    /**
     * Fetch posts by profile and tags
     *
     * @param string Profile ID
     * @param array List of tags for intersection
     * @param integer Start index
     * @param integer Count of results
     * @param string Order ({field} {asc,desc})
     * @return array Posts
     */
    public function fetchByProfileAndTags($profile_id, $tags, $start=0, $count=10, $order='user_date desc')
    {
        return $this->fetchBy(null, null, null, $profile_id, $tags, null, null, $start, $count, $order);
    }

    /**
     * Fetch posts by an arbitrary list of URL hashes for a profile
     *
     * @param array list of URL MD5 hashes
     * @param string Profile ID
     * @return array posts
     */
    public function fetchByHashesAndProfile($hashes, $profile_id)
    {
        if (empty($hashes) || !is_array($hashes))
            throw new Exception('Array of hashes required');

        $posts = $this->fetchBy($hashes, null, null, $profile_id, 
            null, null, null, null, null);

        // HACK: Reorder posts by the arbitrary order of hashes provided
        $posts_by_hash = array();
        $posts_out = array();
        foreach ($posts as $post) 
            $posts_by_hash[$post['hash']] = $post;
        foreach ($hashes as $hash) 
            $posts_out[] = $posts_by_hash[$hash];

        return $posts_out;
    }

    /**
     * Fetch posts for a variety of criteria
     *
     * @param array URL MD5 hashes (null optional)
     * @param string Post UUID (null optional)
     * @param string Profile ID (null optional)
     * @param string Post ID (null optional)
     * @param array List of tags for intersection (null optional)
     * @param integer Start index (null = 0)
     * @param integer Count of results (null = no limit)
     * @param string Order ({field} {asc,desc})
     * @return array Posts
     */
    public function fetchBy($hashes=null, $uuid=null, $id=null, $profile_id=null, $tags=null,
            $start_date=null, $end_date=null, $start=0, $count=10, $order='user_date desc')
    {
        $select = $this->_getPostsSelect();

        if ($order == 'user_date desc')
            $select->orderby('user_date', 'DESC');
        if (null !== $uuid)
            $select->where('posts.uuid', $uuid);
        if (null !== $id)
            $select->where('posts.id', $id);
        if (null !== $profile_id)
            $select->where('posts.profile_id', $profile_id);
        if (null !== $hashes)
            $select->in('urls.hash', $hashes);
        if (null !== $tags)
            $this->_addWhereForTags($select, $tags);
        if (null !== $start_date || null != $end_date) 
            $this->_addWhereForDates($select, $start_date, $end_date);
        if (null !== $count && null !== $start)
            $select->limit($count, $start);

        return $this->_postsRowSetToArray(
            $select->get()->result_array()
        );
    }

    /**
     * Get a count of posts by profile.
     *
     * @param string Profile ID
     * @return integer Count of posts belonging to the profile.
     */
    public function countByProfile($profile_id)
    {
        return $this->countBy($profile_id, null);
    }

    /**
     * Get a count of posts by tag intersection.
     *
     * @param array List of tags
     * @return integer Count of posts belonging to the profile.
     */
    public function countByTags($tags)
    {
        return $this->countBy(null, $tags);
    }

    /**
     * Get a count of posts by profile and tag intersection.
     *
     * @param string Profile ID
     * @param array List of tags
     * @return integer Count of posts belonging to the profile.
     */
    public function countByProfileAndTags($profile_id, $tags)
    {
        return $this->countBy($profile_id, $tags);
    }

    /**
     * Get a count of posts for a variety of criteria
     *
     * @param string Profile ID
     * @param array List of tags
     * @return integer Count of posts belonging to the profile.
     */
    public function countBy($profile_id=null, $tags=null)
    {
        $select = $this->db
            ->select('count(posts.id) as count')
            ->from($this->_table_name);

        if (null !== $profile_id)
            $select->where('posts.profile_id', $profile_id);
        if (null !== $tags)
            $this->_addWhereForTags($select, $tags);

        $row = $select->get()->current();
        return $row['count'];
    }

    /**
     * Delete a post by ID
     *
     * @param string Post ID
     */
    public function deleteById($post_id)
    {
        $data = $this->fetchOneById($post_id);
        if (!$data) return false;

        $this->db->delete($this->_table_name, array('id' => $post_id));

        Event::run('Memex.model_posts.post_deleted', $data);
    }

    /**
     * Delete a post by UUID
     *
     * @param string Post UUID
     */
    public function deleteByUUID($uuid)
    {
        if (empty($uuid)) return false;
        $data = $this->fetchOneByUUID($uuid);
        if (!$data) return false;

        $this->db->delete($this->_table_name, array('uuid' => $uuid));

        Event::run('Memex.model_posts.post_deleted', $data);
    }

    /**
     * Delete a post by URL and profile_id
     *
     * @param string URL
     * @param string Profile ID
     */
    public function deleteByUrlAndProfile($url, $profile_id)
    {
        if (empty($url) || empty($profile_id)) return false;
        $data = $this->fetchOneByUrlAndProfile($url, $profile_id);
        if (null == $data) return null;

        $this->deleteById($data['id']);
        
        Event::run('Memex.model_posts.post_deleted', $data);
    }

    /**
     * Delete all.  Useful for tests, but dangerous otherwise.
     */
    public function deleteAll()
    {
        if (!Kohana::config('model.enable_delete_all'))
            throw new Exception('Mass deletion not enabled');
        $this->db->query('DELETE FROM ' . $this->_table_name);
    }

    /**
     * Convert a row set from the posts table into an array of post 
     * data arrays.
     *
     * @param Zend_Db_Table_Rowset posts rows
     * @return array list of posts
     */
    private function _postsRowSetToArray($posts)
    {
        $tags_model = new Tags_Model();
        $posts_out = array();
        foreach ($posts as $row) {
            $row['tags_parsed'] = 
                $tags_model->parseTags($row['tags']);
            foreach(array('user_date', 'created', 'modified') as $field)
                $row[$field] = date('c', strtotime($row[$field]));
            $posts_out[] = $row;
        }
        return $posts_out;
    }

    /**
     * Build the common select statement for all fetches.
     */
    private function _getPostsSelect()
    {
        return $this->db->select(
                'posts.*, urls.url, urls.hostname, urls.hash, profiles.screen_name'
            )
            ->from('posts')
            ->join('urls', 'urls.id=posts.url_id')
            ->join('profiles', 'profiles.id=posts.profile_id');
    }

    /**
     *
     */
    private function _addWhereForDates($select, $start_date=null, $end_date=null)
    {
        // $db = $this->getDbTable()->getAdapter();
        // $adapter_name = strtolower(get_class($db));
        // if (strpos($adapter_name, 'mysql') !== false) {
            // HACK: MySQL-specific query
            if (null != $start_date)
                $select->where('user_date >=' , date('Y-m-d H:i:s', strtotime($start_date)));
            if (null != $end_date)
                $select->where('user_date <=' , date('Y-m-d H:i:s', strtotime($end_date)));
        // } else {
        //    // HACK: Everything else, assumed ISO8601 date strings like sqlite.
        //    if (null != $start_date)
        //        $select->where('user_date >= ?', $start_date);
        //    if (null != $end_date) 
        //        $select->where('user_date <= ?', $end_date);
        // }
    }

    /**
     * Add clauses to a select statement to implement a tag intersection for a 
     * list of tags.
     *
     * @param Zend_Db_Select Select
     * @param array tags in intersection
     */
    private function _addWhereForTags($select, $tags) 
    {
        if (empty($tags))
            return;
        if (!is_array($tags)) 
            $tags = array($tags);
        
        if (count($tags) == 1) {
            $select
                ->join('tags', 'tags.post_id=posts.id')
                ->where('tags.tag', $tags[0]);
            
        // TODO: Optimize for more common intersections of 2-3
        // } elseif (count($tags) == 2) {
        // } elseif (count($tags) == 3) {

        } else {
            foreach ($tags as $tag) {
                $select->in(
                    'posts.id', 
                    '( SELECT post_id FROM tags WHERE tag=' . $this->db->escape($tag) . ' )'
                );
            }
        }

    }

    /**
     * Build and return a validator
     *
     * @param array Form data to validate.
     */
    public function getValidator($data)
    {
        $valid = Validation::factory($data)
            ->pre_filter('trim')
            ->pre_filter(array('url','normalize'), 'url')
            ->add_rules('url',   'url')
            ->add_rules('title', 'required')
            ->add_rules('notes', 'length[0,1000]')
            ;
        return $valid;
    }

}
