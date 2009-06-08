<?php
/**
 * Logins model
 *
 * @package    Memex
 * @subpackage models
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Logins_Model extends Model
{
    protected $_table_name = 'logins';
    protected $_table_name_password_reset_token =
        'login_password_reset_tokens';
    protected $_table_name_email_verification_token =
        'login_email_verification_tokens';

    /**
     * One-way encrypt a plaintext password, both for storage and comparison 
     * purposes.
     *
     * @param  string cleartext password
     * @return string encrypted password
     */
    public function encrypt_password($password)
    {
        return md5($password);
    }

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
        if ($this->find_by_login_name($data['login_name']))
            throw new Exception('duplicate login name');

        $data = array(
            'login_name' => $data['login_name'],
            'email'      => $data['email'],
            'password'   => $this->encrypt_password($data['password']),
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
    public function register_with_profile($data)
    {
        $new_login = $this->create($data);
        try {
            $profiles_model = new Profiles_Model();
            $new_profile = $profiles_model->create($data);
            $this->add_profile_to_login($new_login['id'], $new_profile['id']);
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
    public function add_profile_to_login($login_id, $profile_id) 
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
     */
    public function find_by_login_name($login_name)
    {
        $row = $this->db->select()->from($this->_table_name)
            ->where('login_name', $login_name)
            ->get()->current();
        if (!$row) return null;
        return $row;
    }

    /**
     * Look up by email
     *
     * @param string email
     */
    public function find_by_email($email)
    {
        $row = $this->db->select()->from($this->_table_name)
            ->where('email', $email)
            ->get()->current();
        if (!$row) return null;
        return $row;
    }

    /**
     * Look up by reset token
     *
     * @param string Screen name
     * @
     */
    public function find_by_password_reset_token($token)
    {
        $row = $this->db->select($this->_table_name . '.*')
            ->from($this->_table_name)
            ->join(
                $this->_table_name_password_reset_token,
                $this->_table_name_password_reset_token . '.login_id',
                $this->_table_name . '.id'
            )
            ->where(
                $this->_table_name_password_reset_token . '.token', 
                $token
            )
            ->get()->current();
        if (!$row) return null;
        return $row;
    }

    /**
     * Look up by reset token
     *
     * @param string Screen name
     * @
     */
    public function find_by_email_verification_token($token)
    {
        $row = $this->db->select(
                $this->_table_name . '.*',
                $this->_table_name_email_verification_token . '.value AS new_email'
            )
            ->from($this->_table_name)
            ->join(
                $this->_table_name_email_verification_token,
                $this->_table_name_email_verification_token . '.login_id',
                $this->_table_name . '.id'
            )
            ->where(
                $this->_table_name_email_verification_token . '.token', 
                $token
            )
            ->get()->current();
        if (!$row) return null;
        return $row;
    }

    /**
     * Fetch the default profile for a login.
     */
    public function find_default_profile_for_login($login_id)
    {
        $profiles = $this->find_profiles_for_login($login_id);
        return (!$profiles) ? null : $profiles[0];
    }

    /**
     * Get all profiles for a login
     */
    public function find_profiles_for_login($login_id)
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
     * Set the password reset token for a given login and return the value 
     * used.
     *
     * @param  string login ID
     * @return string password reset string
     */
    public function set_password_reset_token($login_id)
    {
        $token = md5(uniqid(mt_rand(), true));

        $this->db->delete(
            $this->_table_name_password_reset_token,
            array( 'login_id' => $login_id )
        );
        $rv = $this->db->insert(
            $this->_table_name_password_reset_token,
            array(
                'login_id' => $login_id,
                'token'    => $token
            )
        );
        
        return $token;
    }

    /**
     * Change password for a login.
     * The password reset token, if any, is cleared as well.
     *
     * @param  string  login id
     * @param  string  new password value
     * @return boolean whether or not a password was changed
     */
    public function change_password($login_id, $new_password)
    {
        $crypt_password = $this->encrypt_password($new_password);

        $this->db->delete(
            $this->_table_name_password_reset_token,
            array( 'login_id' => $login_id )
        );
        $rows = $this->db->update(
            'logins', 
            array('password'=>$crypt_password), 
            array('id'=>$login_id)
        );

        return !empty($rows);
    }

    /**
     * Set the password reset token for a given login and return the value 
     * used.
     *
     * @param  string login ID
     * @return string password reset string
     */
    public function set_email_verification_token($login_id, $new_email)
    {
        $token = md5(uniqid(mt_rand(), true));

        $this->db->delete(
            $this->_table_name_email_verification_token,
            array( 'login_id' => $login_id )
        );
        $rv = $this->db->insert(
            $this->_table_name_email_verification_token,
            array(
                'login_id' => $login_id,
                'token'    => $token,
                'value'    => $new_email
            )
        );
        
        return $token;
    }

    /**
     * Change email for a login.
     * The email verification token, if any, is cleared as well.
     *
     * @param  string  login id
     * @param  string  new email value
     * @return boolean whether or not a email was changed
     */
    public function change_email($login_id, $new_email)
    {
        $this->db->delete(
            $this->_table_name_email_verification_token,
            array( 'login_id' => $login_id )
        );
        $rows = $this->db->update(
            'logins', 
            array('email'=>$new_email), 
            array('id'=>$login_id)
        );

        return !empty($rows);
    }

    /**
     * Replace incoming data with registration validator and return whether 
     * validation was successful.
     *
     * @param  array   Form data to validate
     * @return boolean Validation success
     */
    public function validate_registration(&$data)
    {
        $profiles_model = new Profiles_Model();

        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('login_name',       
                'required', 'length[3,64]', 'valid::alpha_dash', 
                array($this, 'is_login_name_available'))
            ->add_rules('email', 'required', 'valid::email')
            ->add_rules('password', 'required')
            ->add_rules('password_confirm', 'required', 'matches[password]')
            ->add_rules('screen_name',      
                'required', 'length[3,64]', 'valid::alpha_dash', 
                array($profiles_model, 'isScreenNameAvailable'))
            ->add_rules('full_name', 'required', 'valid::standard_text')
            ->add_rules('captcha', 'required', 'Captcha::valid')
            ;
        return $data->validate();
    }

    /**
     * Replace incoming data with login validator and return whether 
     * validation was successful.
     *
     * Build and return a validator for the login form
     *
     * @param  array   Form data to validate
     * @return boolean Validation success
     */
    public function validate_login(&$data)
    {
        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('login_name', 'required', 'length[3,64]', 'valid::alpha_dash')
            ->add_rules('password', 'required')
            ->add_callbacks('password', array($this, 'is_password_correct'))
            ;
        return $data->validate();
    }

    /**
     * Replace incoming data with change password validator and return whether 
     * validation was successful, using old password.
     *
     * @param  array   Form data to validate
     * @return boolean Validation success
     */
    public function validate_change_email(&$data)
    {
        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('new_email', 
                'required', 'valid::email')
            ;
        return $data->validate();
    }

    /**
     * Replace incoming data with change password validator and return whether 
     * validation was successful, using old password.
     *
     * @param  array   Form data to validate
     * @return boolean Validation success
     */
    public function validate_change_password(&$data)
    {
        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_callbacks('old_password', 
                array($this, 'is_password_correct'))
            ->add_rules('new_password', 'required')
            ->add_rules('new_password_confirm', 
                'required', 'matches[new_password]')
            ;
        return $data->validate();
    }

    /**
     * Replace incoming data with change password validator and return whether 
     * validation was successful, using forgot password token.
     *
     * @param  array   Form data to validate
     * @return boolean Validation success
     */
    public function validate_change_password_with_token(&$data)
    {
        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('password_reset_token', 
                array($this, 'is_password_reset_token_valid'))
            ->add_rules('new_password', 'required')
            ->add_rules('new_password_confirm', 
                'required', 'matches[new_password]')
            ;
        return $data->validate();
    }

    /**
     * Replace incoming data with forgot password validator and return whether 
     * validation was successful.
     *
     * @param  array   Form data to validate
     * @return boolean Validation success
     */
    public function validate_forgot_password(&$data)
    {
        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('login_name', 'length[3,64]', 'valid::alpha_dash')
            ->add_rules('email', 'valid::email')
            ->add_callbacks('login_name', array($this, 'need_login_name_or_email'))
            ->add_callbacks('email', array($this, 'need_login_name_or_email'))
            ;
        return $data->validate();
    }

    /**
     * Check to see whether a login name is available, for use in form 
     * validator.
     */
    public function is_login_name_available($name)
    {
        $login = $this->find_by_login_name($name);
        return empty($login);
    }

    /**
     * Check to see whether a login name has been registered, for use in form 
     * validator.
     */
    public function is_login_name_registered($name)
    {
        return !($this->is_login_name_available($name));
    }

    /**
     * Check to see whether a given email address has been registered to a 
     * login, for use in form validation.
     */
    public function is_email_registered($email) {
        $login = $this->find_by_email($email);
        return !(empty($login));
    }

    /**
     * Check to see whether a password is correct, for use in form 
     * validator.
     */
    public function is_password_correct($valid, $field)
    {
        $login_name = (isset($valid['login_name'])) ?
            $valid['login_name'] : AuthProfiles::get_login('login_name');
        $login = $this->find_by_login_name($login_name);
        if ($this->encrypt_password($valid[$field]) != $login['password'])
            $valid->add_error($field, 'invalid');
    }

    /**
     * Check whether the given password token is valid.
     *
     * @param  string  password reset token
     * @return boolean 
     */
    public function is_password_reset_token_valid($reset_token)
    {
        // TODO: Do a count() query or something simpler.
        $login = $this->find_by_password_reset_token($reset_token);
        return !empty($login);
    }

    /**
     * Enforce that either an existing login name or email address is supplied 
     * in forgot password validation.
     */
    public function need_login_name_or_email($valid, $field)
    {
        $login_name = (isset($valid['login_name'])) ? 
            $valid['login_name'] : null;
        $email = (isset($valid['email'])) ? 
            $valid['email'] : null;

        if (empty($login_name) && empty($email)) {
            return $valid->add_error($field, 'either');
        }

        if ('login_name' == $field && !empty($login_name)) {
            if (!$this->is_login_name_registered($login_name)) {
                $valid->add_error($field, 'default');
            }
        }

        if ('email' == $field && !empty($email)) {
            if (!$this->is_email_registered($email)) {
                $valid->add_error($field, 'default');
            }
        }
    }

    /**
     * Delete all users from the system.  Useful for tests, but dangerous 
     * otherwise.
     */
    public function delete_all()
    {
        if (!Kohana::config('model.enable_delete_all'))
            throw new Exception('Mass deletion not enabled');
        $this->db->query('DELETE FROM ' . $this->_table_name);
    }

}
