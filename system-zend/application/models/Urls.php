<?php
require_once dirname(__FILE__) . '/Model.php';

/**
 * Model managing known URLs
 */
class Memex_Model_Urls extends Memex_Model
{
    protected $_table_name = 'Urls';

    /**
     * Initialize model
     */
    public function init()
    {
        require_once dirname(__FILE__) . '/Filter/NormalizeUrl.php';
        $this->normalize_url_filter = new Memex_Filter_NormalizeUrl();
    }

    /**
     * Fetch data for a URL by URL
     *
     * @param string URL for lookup
     * @return array URL data
     */
    public function fetchByUrl($url)
    {
        return $this->fetchBy($url, null);
    }

    /**
     * Fetch data for a URL by hash
     *
     * @param string URL for lookup
     * @return array URL data
     */
    public function fetchByHash($hash)
    {
        return $this->fetchBy(null, $hash);
    }

    /**
     * Fetch data for a URL by hash
     *
     * @param string URL for lookup
     * @return array URL data
     */
    public function fetchByUrlOrHash($url, $hash) 
    {
        return $this->fetchBy($url, $hash);
    }

    /**
     * Fetch data using a variety of criteria.
     *
     * @param string URL for lookup
     * @return array URL data
     */
    public function fetchBy($url=null, $hash=null)
    {
        $table = $this->getDbTable();
        $select = $table->select();
        if (null != $url) {
            $url = $this->normalize_url_filter->filter($url);
            //$select->where('url=?', $url);
            $hash = md5($url);
        } 
        if (null != $hash) {
            $select->where('hash=?', $hash);
        }
        $row = $table->fetchRow($select);
        return (null == $row) ? null : $row->toArray();
    }

    /**
     * Fetch an existing record by URL, or create a new one for this URL.
     *
     * @param string URL for lookup
     * @param string Account ID for first URL save
     * @return array URL data
     */
    public function fetchOrCreate($url, $profile_id)
    {
        $url   = $this->normalize_url_filter->filter($url);
        $table = $this->getDbTable();

        // Try fetching an existing URL and return it if found.
        $data = $this->fetchByUrl($url);
        if (null != $data) {
            return $data;
        }

        // Parse the URL for indexable bits
        $url_parts = parse_url($url);

        // Next, create a new URL record and return it.
        $new_id = $table->insert(array(
            'url'              => $url,
            'hash'             => md5($url),
            'hostname'         => empty($url_parts['host']) ? '' : $url_parts['host'],
            'first_profile_id' => $profile_id,
            'created'          => date('Y-m-d H:i:s', time())
        ));
        $rows = $table->find($new_id);
        return ($rows) ? $rows->current()->toArray() : null;
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

}
