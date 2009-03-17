<?php
/**
 * This is the DbTable class for the logins table.
 */
class Logins_Model extends Model
{
    protected $_table_name = 'logins';

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

        $data = array(
            'login_name' => $data['login_name'],
            'email'      => $data['email'],
            'password'   => md5($data['password']),
            'created'    => gmdate('Y-m-d H:i:s', time())
        );
        $data['id'] = $this->db
            ->insert($this->_table_name, $data)
            ->insert_id();

        return $data;
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
        $this->db->delete($this->_table_name, array('id'=>$id));
    }

    /**
     * Create a new login and associated profile.
     */
    public function registerWithProfile($data)
    {
        $new_login = $this->create($data);
        try {
            $profiles_model = new Profiles_Model();
            $new_profile = $profiles_model->create($data);
            $this->addProfileToLogin($new_login['id'], $new_profile['id']);
        } catch (Exception $e) {
            // If profile creation failed, delete the login.
            // TODO: Transaction here?
            $this->delete($new_login['id']);
            throw $e;
        }
        return $new_login;
    }

    /**
     * Link an profile with this login
     */
    public function addProfileToLogin($login_id, $profile_id) 
    {
        return $this->db->insert(
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
        $row = $this->db->select()
            ->from($this->_table_name)
            ->where('login_name', $login_name)
            ->get()->current();
        if (!$row) return null;
        return $row;
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
        $login_row = $this->db->select()
            ->from($this->_table_name)
            ->where('id', $login_id)
            ->get()->current();

        if (null == $login_row) return null;

        $profile_rows = $this->db
            ->select('profiles.*')
            ->from('profiles')
            ->join('logins_profiles', 'logins_profiles.profile_id=profiles.id')
            ->where('logins_profiles.login_id', $login_row['id'])
            ->get()->result_array();

        $profiles = array();
        foreach ($profile_rows as $row)
            $profiles[] = $row;

        return $profiles;
    }

    /**
     * Build and return a validator for a registration form
     *
     * @param array Form data to validate.
     */
    public function validateRegistration(&$data)
    {
        $profiles_model = new Profiles_Model();

        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('login_name',       
                'required', 'length[3,64]', 'valid::alpha_dash', 
                array($this, 'isLoginNameAvailable'))
            ->add_rules('email',            
                'required', 'valid::email')
            ->add_rules('password',         
                'required')
            ->add_rules('password_confirm', 
                'required', 'matches[password]')
            ->add_rules('screen_name',      
                'required', 'length[3,64]', 'valid::alpha_dash', 
                array($profiles_model, 'isScreenNameAvailable'))
            ->add_rules('full_name',        
                'required', 'valid::standard_text')
            ->add_rules('captcha',          
                'required', 'Captcha::valid')
            ;
        $is_valid = $data->validate();

        return $is_valid;
    }

    /**
     * Build and return a validator for the login form
     *
     * @param array Form data to validate
     */
    public function validateLogin(&$data)
    {
        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('login_name', 'required', 'length[3,64]', 'valid::alpha_dash')
            ->add_rules('password', 'required')
            ->add_callbacks('password', array($this, 'isPasswordValid'))
            ;
        $is_valid = $data->validate();

        return $is_valid;
    }

    /**
     * Check to see whether a login name is available, for use in form 
     * validator.
     */
    public function isLoginNameAvailable($name)
    {
        $login = $this->fetchByLoginName($name);
        return empty($login);
    }

    /**
     * Check to see whether a login name is available, for use in form 
     * validator.
     */
    public function isPasswordValid($valid, $field)
    {
        $login = $this->fetchByLoginName($valid['login_name']);
        if (md5($valid[$field]) != $login['password'])
            $valid->add_error($field, 'invalid');
    }

    /**
     * Delete all users from the system.  Useful for tests, but dangerous 
     * otherwise.
     */
    public function deleteAll()
    {
        if (!Kohana::config('model.enable_delete_all'))
            throw new Exception('Mass deletion not enabled');
        $this->db->query('DELETE FROM ' . $this->_table_name);
    }

}
