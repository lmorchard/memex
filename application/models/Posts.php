<?php
require_once dirname(__FILE__) . '/Model.php';

/**
 * Model managing posts
 */
class Memex_Model_Posts extends Memex_Model
{
    protected $_table_name = 'Posts';

    /**
     * Initialize the model.
     */
    function init() 
    {
        require_once dirname(__FILE__) . '/Filter/NormalizeUrl.php';
        $this->normalize_url_filter = new Memex_Filter_NormalizeUrl();
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
            $post_data['date'] = date('Y-m-d\TH:i:sP', $date_in);
        }

        $table = $this->getDbTable();

        // Get an ID for the post's URL and set the ID in post data
        $urls_model = $this->getModel('Urls');
        $url_data = $urls_model->fetchOrCreate(
            $post_data['url'], $post_data['profile_id']
        );
        $post_data['url_id'] = $url_data['id'];

        // Try looking up an existing post for this URL and profile.
        $row = null;
        if (!empty($post_data['uuid'])) {
            $row = $table->fetchRow($table->select()
                ->where('uuid=?', $post_data['uuid'])
            );
        } elseif (!empty($post_data['id'])) {
            $row = $table->fetchRow($table->select()
                ->where('id=?', $post_data['id'])
            );
        } else {
            $row = $table->fetchRow($table->select()
                ->where('url_id=?', $url_data['id'])
                ->where('profile_id=?', $post_data['profile_id'])
            );
        }

        // If there's no existing post, create a new one.
        if (null == $row) {
            $row = $table->createRow()->setFromArray(array(
                'uuid'       => $this->uuid(),
                'url_id'     => $url_data['id'],
                'profile_id' => $post_data['profile_id']
            ));
        }

        // Has the URL been changed in an existing post?
        if ($row['url_id'] != $url_data['id']) {
            // TODO: Delete URL record if last bookmark reference gone?
            // Probably a good job for an offline queue.
        }

        // Update the post's data and save it.  Note that only a select set of 
        // fields are used, which prevents changes in UUID and others
        $accepted_post_fields = array(
            'profile_id', 'url_id', 'title', 'notes', 'tags', 
            'visibility', 'user_date'
        );
        foreach ($accepted_post_fields as $key) {
            if (isset($post_data[$key]))
                $row->$key = $post_data[$key];
        }
        $row->save();
        
        // HACK: Re-fetch the just-saved post.  Ensures consistent data, but 
        // probably needs some work to avoid cache issues later on.
        $saved_post = $this->fetchOneById($row->id);

        // Update the tags for this post.
        // TODO: This can be separated out into a deferred queue task
        $tags_model = $this->getModel('Tags');
        $tags_model->updateTagsForPost($saved_post);

        // Return the results of the save.
        return $saved_post;
    }

    /**
     * Fetch post by post ID
     *
     * @param string Post ID
     * @return array A single post
     */
    public function fetchOneById($id) {
        return $this->fetchOneBy($id, null, null, null);
    }

    /**
     * Fetch post by post UUID
     *
     * @param string Post UUID
     * @return array A single post
     */
    public function fetchOneByUUID($uuid) {
        return $this->fetchOneBy(null, null, $uuid, null);
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
        return $this->fetchOneBy(null, $url, null, $profile_id);
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
    public function fetchOneBy($id=null, $url=null, $uuid=null, $profile_id=null)
    {
        // Try looking up an existing post for this URL and profile.
        $table = $this->getDbTable();
        $select = $this->_getPostsSelect();

        if (null != $profile_id) 
            $select->where('profile_id=?', $profile_id);
        if (null != $id)
            $select->where('posts.id=?', $id);
        if (null != $uuid)
            $select->where('posts.uuid=?', $uuid);
        if (null != $url)
            $select->where('urls.url=?', 
                $this->normalize_url_filter->filter($url));

        $data = $this->_postsRowSetToArray(
            $table->fetchAll($select)
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
        return $this->fetchBy(null, null, null, $tags, $start, $count, $order);
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
        return $this->fetchBy(null, null, $profile_id, $tags, $start, $count, $order);
    }

    /**
     * Fetch posts for a variety of criteria
     *
     * @param string Profile ID
     * @param array List of tags for intersection
     * @param integer Start index
     * @param integer Count of results
     * @param string Order ({field} {asc,desc})
     * @return array Posts
     */
    public function fetchBy($uuid=null, $id=null, $profile_id=null, $tags=null, $start=0, $count=10, $order='user_date desc')
    {
        $table  = $this->getDbTable();
        $select = $this->_getPostsSelect();

        if ($order == 'user_date desc')
            $select->order('user_date DESC');
        if (null !== $uuid)
            $select->where('posts.uuid=?', $uuid);
        if (null !== $id)
            $select->where('posts.id=?', $id);
        if (null !== $profile_id)
            $select->where('posts.profile_id=?', $profile_id);
        if (null !== $tags)
            $this->_addWhereForTags($select, $tags);
        if (null !== $count && null !== $start)
            $select->limit($count, $start);

        return $this->_postsRowSetToArray(
            $table->fetchAll($select)
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
        $table = $this->getDbTable();
        $select = $table->select()
            ->from($table, 'count(posts.id) as count');

        if (null !== $profile_id)
            $select->where('posts.profile_id=?', $profile_id);
        if (null !== $tags)
            $this->_addWhereForTags($select, $tags);

        $row = $table->fetchRow($select);
        return $row['count'];
    }

    /**
     * Delete a post by ID
     *
     * @param string Post ID
     */
    public function deleteById($post_id)
    {
        $table = $this->getDbTable();
        $rv = $table->delete(
            $table->getAdapter()->quoteInto('id=?', $post_id)
        );
        return $rv;
    }

    /**
     * Delete a post by UUID
     *
     * @param string Post UUID
     */
    public function deleteByUUID($uuid)
    {
        $table = $this->getDbTable();
        $rv = $table->delete(
            $table->getAdapter()->quoteInto('uuid=?', $uuid)
        );
        return $rv;
    }

    /**
     * Delete a post by URL and profile_id
     *
     * @param string URL
     * @param string Profile ID
     */
    public function deleteByUrlAndProfile($url, $profile_id)
    {
        $data = $this->fetchOneByUrlAndProfile($url, $profile_id);
        if (null == $data) return null;
        return $this->deleteById($data['id']);
    }

    /**
     * Delete all.  Useful for tests, but dangerous otherwise.
     */
    public function deleteAll()
    {
        if (!Zend_Registry::get('config')->model->enable_delete_all)
            throw new Exception('Mass deletion not enabled');
        $this->getDbTable()->delete('');
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
        $tags_model = $this->getModel('Tags');
        $posts_out = array();
        foreach ($posts as $row) {
            $row_data = $row->toArray();
            $row_data['tags_parsed'] = 
                $tags_model->parseTags($row_data['tags']);
            $posts_out[] = $row_data;
        }
        return $posts_out;
    }

    /**
     * Build the common select statement for all fetches.
     */
    private function _getPostsSelect()
    {
        return $this->getDbTable()->select()
            ->setIntegrityCheck(false)
            ->from('posts')
            ->join(
                'urls', 
                'urls.id=posts.url_id', 
                array('urls.url', 'urls.hostname', 'urls.hash')
            )
            ->join(
                'profiles', 
                'profiles.id=posts.profile_id', 
                array('profiles.screen_name')
            );
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
        
        $select->setIntegrityCheck(false);
        if (count($tags) == 1) {
            $select
                ->join('tags', 'tags.post_id=posts.id', array())
                ->where('tags.tag=?', $tags[0]);
            
        // TODO: Optimize for more common intersections of 2-3
        // } elseif (count($tags) == 2) {
        // } elseif (count($tags) == 3) {

        } else {
            foreach ($tags as $tag) {
                $select->where(
                    'posts.id IN ( SELECT post_id FROM tags WHERE tag=? )', 
                    $tag
                );
            }
        }

    }

}
