<?php
require_once dirname(__FILE__) . '/Model.php';

/**
 * This is the DbTable class for the profiles table.
 */
class Memex_Model_Profiles extends Memex_Model
{
    protected $_table_name = 'Profiles';

    /**
     * Create a new profile
     *
     * @param array profile data
     * @return string New profile ID
     */
    public function create($data)
    {
        if (empty($data['screen_name']))
            throw new Exception('screen_name required');
        if (empty($data['full_name']))
            throw new Exception('full_name required');
        if ($this->fetchByScreenName($data['screen_name']))
            throw new Exception('duplicate screen name');

        $table = $this->getDbTable();

        $row = $table->createRow()->setFromArray(array(
            'uuid'        => $this->uuid(),
            'screen_name' => $data['screen_name'],
            'full_name'   => $data['full_name'],
            'bio'         => empty($data['bio']) ? '' : $data['bio'],
            'created'     => date('Y-m-d H:i:s', time())
        ));
        $row->save();

        return $row->toArray();
    }

    /**
     * Look up by screen name
     *
     * @param string Screen name
     * @
     */
    public function fetchByScreenName($screen_name)
    {
        $table = $this->getDbTable();
        $row = $table->fetchRow(
            $table->select()->where('screen_name=?', $screen_name)
        );
        if (null == $row) return false;
        $data = $row->toArray();
        return $data;
    }

    /**
     * Delete all profiles from the system.  Useful for tests, but dangerous 
     * otherwise.
     */
    public function deleteAll()
    {
        if ('testing' != APPLICATION_ENVIRONMENT)
            throw new Exception('Mass deletion only supported during testing');
        $this->getDbTable()->delete('');
    }

}
