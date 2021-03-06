<?php
/**
 * Model managing known URLs
 *
 * @package    Memex
 * @subpackage models
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Urls_Model extends Model
{
    protected $_table_name = 'urls';

    /**
     * Fetch data for a URL by URL
     *
     * @param string URL for lookup
     * @return array URL data
     */
    public function findByUrl($url)
    {
        return $this->findBy($url, null);
    }

    /**
     * Fetch data for a URL by hash
     *
     * @param string URL for lookup
     * @return array URL data
     */
    public function findByHash($hash)
    {
        return $this->findBy(null, $hash);
    }

    /**
     * Fetch data for a URL by hash
     *
     * @param string URL for lookup
     * @return array URL data
     */
    public function findByUrlOrHash($url, $hash) 
    {
        return $this->findBy($url, $hash);
    }

    /**
     * Fetch data using a variety of criteria.
     *
     * @param string URL for lookup
     * @return array URL data
     */
    public function findBy($url=null, $hash=null)
    {
        $select = $this->db->select()
            ->from($this->_table_name);
        if (null != $url) {
            $url = url::normalize($url);
            $hash = md5($url);
        } 
        if (null != $hash) {
            $select->where('hash', $hash);
        }
        return $select->get()->current();
    }

    /**
     * Fetch an existing record by URL, or create a new one for this URL.
     *
     * @param string URL for lookup
     * @param string Account ID for first URL save
     * @return array URL data
     */
    public function findOrCreate($url, $profile_id)
    {
        $url = url::normalize($url);

        // Try finding an existing URL and return it if found.
        $data = $this->findByUrl($url);
        if (null != $data) {
            return $data;
        }

        // Parse the URL for indexable bits
        $url_parts = parse_url($url);

        // Next, create a new URL record and return it.
        $new_id = $this->db->insert(
            $this->_table_name,
            array(
                'url'              => $url,
                'hash'             => md5($url),
                'hostname'         => empty($url_parts['host']) ? '' : $url_parts['host'],
                'first_profile_id' => $profile_id,
                'created'          => gmdate('c')
            )
        )->insert_id();
        return $this->db->select()
            ->from($this->_table_name)
            ->where('id', $new_id)
            ->get()->current();
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
