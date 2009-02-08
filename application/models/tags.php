<?php
/**
 * Model managing known URLs
 */
class Tags_Model extends Model
{
    protected $_table_name = 'tags';

    /**
     * Initialize model
     */
    public function init()
    {
    }

    /**
     * Handle notification for an updated post
     */
    public function handlePostUpdated($topic, $post_data, $context)
    {
        return $this->updateTagsForPost($post_data);
    }

    /**
     * Handle notification for a deleted post
     */
    public function handlePostDeleted($topic, $post_data, $context)
    {
        return $this->deleteTagsForPost($post_data['id']);
    }

    /**
     * Parse a string of user input input containing tags.
     *
     * TODO: More flexible parsing beyond space delimiting, including commas and quotes
     *
     * @param string Tags from user input
     * @return array List of tags
     */
    public function parseTags($tags_string)
    {
        $tags_split = explode(' ', trim($tags_string));
        $tags = array();
        foreach ($tags_split as $tag) {
            $tag = trim($tag);
            if ($tag) $tags[] = $tag;
        }
        return $tags;
    }

    /**
     * Return a string representation of a list of tags.
     * Currently, this is just joining by spaces but could be more complex 
     * soon.
     *
     * @param array list of tags
     * @return string
     */
    public function concatenateTags($tags)
    {
        if (null == $tags) return '';
        return join(' ', $tags);
    }

    /**
     * Count tags by profile ID.
     *
     * @param string profile ID
     * @param integer start index
     * @param integer result limit
     * @param integer tag count threshold
     * @param string order results by "count desc" or "tag asc"
     * @return array list of tag counts
     */
    public function countByProfile($profile_id, $start=0, $count=10, $threshold=null, $order='count desc')
    {
        return $this->countBy($profile_id, $start, $count, $threshold, $order);
    }

    /**
     * Fetch and count tags by a variety of criteria.
     *
     * @param string profile ID
     * @param integer start index
     * @param integer result limit
     * @param integer tag count threshold
     * @param string order results by "count desc" or "tag asc"
     * @return array list of tag counts
     */
    public function countBy($profile_id=null, $start=0, $count=10, $threshold=null, $order='count desc')
    {
        $select = $this->db
            ->select('tag as tag, count(id) as count')
            ->from($this->_table_name)
            ->groupby('tag');

        if (null !== $profile_id)
            $select->where('profile_id', $profile_id);
        if (null !== $threshold)
            $select->where('count>', $threshold);
        if ('count desc' == $order)
            $select->orderby('count', 'desc');

        $select->orderby('tag', 'asc');
        
        if (null !== $start && null !== $count)
            $select->limit($count, $start);

        return $select->get()->result_array();
    }

    /**
     * Fetch tag records by tag name and profile.
     */
    public function fetchByTagAndProfile($tag_name, $profile_id)
    {
        return $this->db->select()
            ->from($this->_table_name)
            ->where('tag', $tag_name)
            ->where('profile_id', $profile_id)
            ->get()->current();
    }

    /**
     * Fetch tag records for a given post, in position order.
     */
    public function fetchByPost($post_id)
    {
        return $this->db->select()
            ->from($this->_table_name)
            ->where('post_id', $post_id)
            ->orderby('position')
            ->get()->result_array();
    }

    /**
     * Delete tags for a given post.
     *
     * @param string post ID
     */
    public function deleteTagsForPost($post_id)
    {
        $this->db->delete(
            $this->_table_name, 
            array('post_id' => $post_id)
        );
    }

    /**
     * For a given post, update the individual tag records to reflect updated 
     * in the post.
     */
    public function updateTagsForPost($post_data)
    {
        $posts    = new Posts_Model();
        $new_tags = $this->parseTags($post_data['tags']);

        // Look up all existing tags for the post.
        $tag_rows = $this->db->select()
            ->from($this->_table_name)
            ->where('post_id', $post_data['id'])
            ->get();

        $old_tags = array();
        foreach ($tag_rows as $row) {
            $old_tags[] = $row['tag'];
        }

        // The existing tags to delete are the difference between old and new
        $delete_tags = array_diff($old_tags, $new_tags);
        foreach ($delete_tags as $tag) {
            $this->db->delete(
                $this->_table_name, 
                array(
                    'post_id' => $post_data['id'],
                    'tag'     => $tag
                )
            );
        }

        // The new tags to add are the difference between new and old
        $create_tags = array_diff($new_tags, $old_tags);
        foreach ($create_tags as $tag) {
            $this->db->insert(
                $this->_table_name,
                array( 
                    'tag'        => $tag,
                    'post_id'    => $post_data['id'], 
                    'profile_id' => $post_data['profile_id'], 
                    'url_id'     => $post_data['url_id'],
                    'created'    => date('c'),
                    'modified'   => date('c')
                )
            );
        }

        // Update the position index on all the updated tags.
        foreach ($new_tags as $position=>$tag) {
            $this->db->update(
                $this->_table_name,
                array( 
                    'position' => $position,
                    'modified' => date('c')
                ),
                array(
                    'post_id'  => $post_data['id'],
                    'tag'      => $tag
                )
            );
        }

        return $this;
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

}
