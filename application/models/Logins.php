<?php
require_once dirname(__FILE__) . '/Model.php';

/**
 * This is the DbTable class for the logins table.
 */
class Memex_Model_Logins extends Memex_Model
{
    protected $_table_name = 'Logins';

    /**
     * Create a new login
     *
     * @param array Login data
     * @return string New login ID
     */
    public function create($data)
    {
        if (empty($data['login_name']))
            throw new Exception('login_name required');
        if (empty($data['email']))
            throw new Exception('email required');
        if (empty($data['password']))
            throw new Exception('password required');
        if ($this->fetchByLoginName($data['login_name']))
            throw new Exception('duplicate login name');

        $table = $this->getDbTable();

        $id = $table->insert(array(
            'login_name' => $data['login_name'],
            'email'      => $data['email'],
            'password'   => md5($data['password']),
            'created'    => date('Y-m-d H:i:s', time())
        ));

        return $id;
    }

    /**
     * Delete a login.  
     *
     * Note that this does not cascadingly delete profiles or anything else, 
     * since profiles are the primary resource here and multiple logins may be 
     * attached to a single profile.
     *
     * @param string Login ID
     */
    public function delete($id) {
        $this->getDbTable()->delete($id);
    }

    /**
     * Create a new login and associated profile.
     */
    public function registerWithProfile($data)
    {
        $new_login_id = $this->create($data);
        try {
            $new_profile_id = $this->getModel('Profiles')->create($data);
            $this->addProfileToLogin($new_login_id, $new_profile_id);
        } catch (Exception $e) {
            // If profile creation failed, delete the login.
            $this->delete($new_login_id);
            throw $e;
        }
        return $new_login_id;
    }

    /**
     * Link an profile with this login
     */
    public function addProfileToLogin($login_id, $profile_id) 
    {
        return $this->getDbTable()->getAdapter()->insert(
            'logins_profiles', array(
                'login_id'   => $login_id, 
                'profile_id' => $profile_id
            )
        );
    }

    /**
     * Look up by login name
     *
     * @param string Screen name
     * @
     */
    public function fetchByLoginName($login_name)
    {
        $table = $this->getDbTable();
        $row = $table->fetchRow(
            $table->select()->where('login_name=?', $login_name)
        );
        if (null == $row) return null;
        $data = $row->toArray();
        return $data;
    }

    /**
     * Fetch the default profile for a login.
     */
    public function fetchDefaultProfileForLogin($login_id)
    {
        $profiles = $this->fetchProfilesForLogin($login_id);
        return (!$profiles) ? null : $profiles[0];
    }

    /**
     * Get all profiles for a login
     */
    public function fetchProfilesForLogin($login_id)
    {
        $login_row = $this->getDbTable()->find($login_id)->current();
        if (null == $login_row) return null;

        $profile_rows = $login_row->findManyToManyRowset(
            'Memex_Db_Table_Profiles', 
            'Memex_Db_Table_LoginsProfiles'
        );
        $profiles = array();
        foreach ($profile_rows as $row)
            $profiles[] = $row->toArray();

        return $profiles;
    }

    /**
     * Delete all users from the system.  Useful for tests, but dangerous 
     * otherwise.
     */
    public function deleteAll()
    {
        if ('testing' != APPLICATION_ENVIRONMENT)
            throw new Exception('Mass deletion only supported during testing');
        $this->getDbTable()->delete('');
    }

}
