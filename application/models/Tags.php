<?php
require_once dirname(__FILE__) . '/Model.php';

/**
 * Model managing known URLs
 */
class Memex_Model_Tags extends Memex_Model
{
    protected $_table_name  ='Tags';

    /**
     * Initialize model
     */
    public function init()
    {
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
        $table = $this->getDbTable();
        $select = $table->select()
            ->from($table, array('tag', 'count(id) as count'))
            ->group('tag');

        if (null !== $profile_id)
            $select->where('profile_id=?', $profile_id);
        if (null !== $threshold)
            $select->where('count>?', $threshold);
        if ('count desc' == $order)
            $select->order(array('count desc'));
        if ('tag' == $order || 'tag asc' == $order)
            $select->order(array('tag asc'));
        if (null !== $start && null !== $count)
            $select->limit($count, $start);

        $rows = $table->fetchAll($select);

        $tag_counts = array();
        foreach ($rows as $row) {
            $tag_counts[] = $row->toArray();
        }
        return $tag_counts;
    }

    /**
     * Fetch tag records by tag name and profile.
     */
    public function fetchByTagAndProfile($tag_name, $profile_id)
    {
        $table = $this->getDbTable();
        $row = $table->fetchRow($table->select()
            ->where('tag=?', $tag_name)
            ->where('profile_id=?', $profile_id)
        );
        return (null == $row) ? null : $row->toArray();
    }

    /**
     * For a given post, update the individual tag records to reflect updated 
     * in the post.
     */
    public function updateTagsForPost($post_data)
    {
        $table    = $this->getDbTable();
        $db       = $table->getAdapter();
        $posts    = $this->getModel('Posts');
        $new_tags = $this->parseTags($post_data['tags']);

        // Look up all existing tags for the post.
        $tag_rows = $table->fetchAll(
            $table->select()->where('post_id=?', $post_data['id'])
        );
        $old_tags = array();
        foreach ($tag_rows as $row) {
            $old_tags[] = $row['tag'];
        }

        // The existing tags to delete are the difference between old and new
        $delete_tags = array_diff($old_tags, $new_tags);
        foreach ($delete_tags as $tag) {
            $table->delete(array(
                $db->quoteInto('post_id=?', $post_data['id']),
                $db->quoteInto('tag=?', $tag)
            ));
        }

        // The new tags to add are the difference between new and old
        $create_tags = array_diff($new_tags, $old_tags);
        foreach ($create_tags as $tag) {
            $table->insert(array( 
                'tag'        => $tag,
                'post_id'    => $post_data['id'], 
                'profile_id' => $post_data['profile_id'], 
                'url_id'     => $post_data['url_id']
            ));
        }

        // Update the position index on all the updated tags.
        foreach ($new_tags as $position=>$tag) {
            $table->update(
                array( 'position' => $position ),
                array(
                    $db->quoteInto('post_id=?', $post_data['id']),
                    $db->quoteInto('tag=?', $tag)
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
        if (!Zend_Registry::get('config')->model->enable_delete_all)
            throw new Exception('Mass deletion only supported during testing');
        $this->getDbTable()->delete('');
    }

}
