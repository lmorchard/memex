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
     * Update an existing profile
     *
     * @param array Array of profile data for update
     * @return array Updated profile data
     */
    public function update($data)
    {
        if (empty($data['id']))
            throw new Exception('id required');

        $table = $this->getDbTable();
        $profile = $table->fetchRow(
            $table->select()->where('id=?', $data['id'])
        );
        $accepted_fields = array(
            'screen_name', 'full_name', 'bio'
        );
        foreach ($accepted_fields as $key) {
            if (isset($data[$key]))
                $profile->$key = $data[$key];
        }
        $profile->save();

        return $profile->toArray();
    }

    /**
     * Look up by id
     *
     * @param string profile id
     * @return array profile data
     */
    public function fetchById($profile_id)
    {
        return $this->fetchOneBy($profile_id, null);
    }

    /**
     * Look up by screen name
     *
     * @param string Screen name
     * @return array profile data
     */
    public function fetchByScreenName($screen_name)
    {
        return $this->fetchOneBy(null, $screen_name);
    }

    /**
     * Look up by a variety of criteria
     *
     * @param string profile id
     * @param string Screen name
     * @return array profile data
     */
    public function fetchOneBy($id=null, $screen_name=null)
    {
        $table = $this->getDbTable();
        $select = $table->select();
        if (null != $id)
            $select->where('id=?', $id);
        if (null != $screen_name)
            $select->where('screen_name=?', $screen_name);
        $row = $table->fetchRow($select);
        if (null == $row) return false;
        $data = $row->toArray();
        return $data;
    }

    /**
     * Set a profile attribute
     *
     * @param string Profile ID
     * @param string Profile attribute name
     * @param string Profile attribute value
     */
    public function setAttribute($profile_id, $name, $value)
    {
        $table = $this->getDbTable('ProfileAttribs');
        $select = $table->select()
            ->where('profile_id=?', $profile_id)
            ->where('name=?', $name);
        $row = $table->fetchRow($select);
        if (null == $row) {
            $row = $table->createRow()->setFromArray(array(
                'profile_id' => $profile_id,
                'name'       => $name
            ));
        }
        $row->value = $value;
        $row->save();
        return $row->toArray();
    }

    /**
     * Set profile attributes
     *
     * @param string Profile ID
     * @param array list of profile attributes
     */
    public function setAttributes($profile_id, $attributes)
    {
        foreach ($attributes as $name=>$value) {
            $this->setAttribute($profile_id, $name, $value);
        }
    }

    /**
     * Get a profile attribute
     *
     * @param string Profile ID
     * @param string Profile attribute name
     * @return string Attribute value 
     */
    public function getAttribute($profile_id, $name)
    {
        $table = $this->getDbTable('ProfileAttribs');
        $select = $table->select()
            ->from($table, array('value'))
            ->where('profile_id=?', $profile_id)
            ->where('name=?', $name);
        $row = $table->fetchRow($select);
        if (null == $row) return false;
        return $row['value'];
    }

    /**
     * Get all profile attributes
     *
     * @param string Profile ID
     * @return array Profile attributes
     */
    public function getAttributes($profile_id, $names=null)
    {
        $table  = $this->getDbTable('ProfileAttribs');
        $db     = $table->getAdapter();
        $select = $table->select()
            ->where('profile_id=?', $profile_id);
        if (null != $names) {
            $names_where = array();
            foreach ($names as $name) {
                $names_where[] = $db->quoteInto('name=?', $name);
            }
            $select->where(join(' OR ', $names_where));
        }
        $rows = $table->fetchAll($select)->toArray();
        $attribs = array();
        foreach ($rows as $row) {
            $attribs[$row['name']] = $row['value'];
        }
        return $attribs;
    }

    /**
     * Delete all profiles from the system.  Useful for tests, but dangerous 
     * otherwise.
     */
    public function deleteAll()
    {
        if (!Zend_Registry::get('config')->model->enable_delete_all)
            throw new Exception('Mass deletion not enabled');
        $this->getDbTable()->delete('');
    }

}
