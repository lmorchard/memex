<?php
/**
 * Login model
 *
 * @package    auth_profiles
 * @subpackage models
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Login_Model extends ORM
{
    // {{{ Model attributes
    
    protected $has_and_belongs_to_many = array('profiles');
    protected $has_many = array('profile_attributes');

    protected $_table_name_password_reset_token =
        'login_password_reset_tokens';
    protected $_table_name_email_verification_token =
        'login_email_verification_tokens';

    // }}}

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
	 * Returns the unique key for a specific value. This method is expected
	 * to be overloaded in models if the model has other unique columns.
	 *
     * If the key used in a find is a non-numeric string, search 'login_name' column.
     *
	 * @param   mixed   unique value
	 * @return  string
     */
    public function unique_key($id)
    {
        if (!empty($id) && is_string($id) && !ctype_digit($id)) {
            return 'login_name';
        }
        return parent::unique_key($id);
    }

    /**
     * Before saving, update created/modified timestamps and generate a UUID if 
     * necessary.
     *
	 * @chainable
	 * @return  ORM
     */
    public function save()
    {
        if (isset($this->table_columns['password']) && !empty($this->password)) {
            $this->password = $this->encrypt_password($this->password);
        }
        return parent::save();
    }


    /**
     * Create a new login and associated profile.
     */
    public function register_with_profile($data, $force_email_verified=false)
    {
        $profile_data = array(
            'login_name' => $data['login_name'],
            'email'      => ($force_email_verified) ? $data['email'] : '',
            'password'   => $data['password'],
            'created'    => gmdate('c', time())
        );

        $new_login = ORM::factory('login')->set($profile_data)->save();

        $profile_data['id'] = $new_login->id;

        if (!$force_email_verified) {
            $profile_data['new_email'] = $data['email'];
            $profile_data['email_verification_token'] = 
                $this->set_email_verification_token(
                    $new_login->id, $data['email']
                );
        }

        $new_profile = ORM::factory('profile')->set($data)->save();

        $new_login->add($new_profile);
        $new_login->save();

        return arr::to_object($profile_data);
    }

    /**
     * Find the default profile for this login, usually the first registered.
     */
    public function find_default_profile_for_login()
    {
        if (!$this->loaded) return null;
        $profiles = $this->profiles;
        return $profiles[0];
    }


    /**
     * Set the password reset token for a given login and return the value 
     * used.
     *
     * @param  string login ID
     * @return string password reset string
     */
    public function set_password_reset_token()
    {
        if (!$this->loaded) return;

        $token = md5(uniqid(mt_rand(), true));

        $this->db->delete(
            $this->_table_name_password_reset_token,
            array( 'login_id' => $this->id )
        );
        $rv = $this->db->insert(
            $this->_table_name_password_reset_token,
            array(
                'login_id' => $this->id,
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
    public function change_password($new_password)
    {
        if (!$this->loaded) return;

        $crypt_password = $this->encrypt_password($new_password);

        $this->db->delete(
            $this->_table_name_password_reset_token,
            array( 'login_id' => $this->id )
        );
        $rows = $this->db->update(
            'logins', 
            array('password'=>$crypt_password), 
            array('id'=>$this->id)
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
    public function set_email_verification_token($new_email)
    {
        if (!$this->loaded) return;

        $token = md5(uniqid(mt_rand(), true));

        $this->db->delete(
            $this->_table_name_email_verification_token,
            array( 'login_id' => $this->id )
        );
        $rv = $this->db->insert(
            $this->_table_name_email_verification_token,
            array(
                'login_id' => $this->id,
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
    public function change_email($new_email)
    {
        if (!$this->loaded) return;
        $this->db->delete(
            $this->_table_name_email_verification_token,
            array( 'login_id' => $this->id )
        );
        $rows = $this->db->update(
            'logins', 
            array('email'=>$new_email), 
            array('id'=>$this->id)
        );
        return !empty($rows);
    }


    /**
     * Find by password reset token
     *
     * @param  string token value
     * @return Login_Model
     */
    public function find_by_password_reset_token($token)
    {
        return ORM::factory('login')
            ->join(
                $this->_table_name_password_reset_token,
                "{$this->_table_name_password_reset_token}.login_id",
                "{$this->table_name}.id"
            )
            ->where(
                "{$this->_table_name_password_reset_token}.token",
                $token
            )
            ->find();
    }

    /**
     * Find by email verification token
     *
     * @param  string token value
     * @return Login_Model
     */
    public function find_by_email_verification_token($token)
    {
        $row = $this->db
            ->select('value, login_id')
            ->from($this->_table_name_email_verification_token)
            ->where('token', $token)
            ->get()->current();

        if (!$row) {
            return array(null, null);
        } else {
            return array(
                ORM::factory('login', $row->login_id),
                $row->value
            );
        }
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
        $profile_model = new Profile_Model();

        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('login_name',       
                'required', 'length[3,64]', 'valid::alpha_dash', 
                array($this, 'is_login_name_available'))
            ->add_rules('email', 
                'required', 'length[3,255]', 'valid::email',
                array($this, 'is_email_available'))
            ->add_rules('email_confirm', 
                'required', 'valid::email', 'matches[email]')
            ->add_rules('password', 'required')
            ->add_rules('password_confirm', 'required', 'matches[password]')
            ->add_rules('screen_name',      
                'required', 'length[3,64]', 'valid::alpha_dash', 
                array($profile_model, 'is_screen_name_available'))
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
            ->add_rules('login_name', 'required', 'length[3,64]', 
                'valid::alpha_dash', array($this, 'is_login_name_registered'))
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
                'required', 'length[3,255]', 'valid::email',
                array($this, 'is_email_available'))
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
        $count = $this->db
            ->where('login_name', $name)
            ->count_records($this->table_name);
        return (0==$count);
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
    public function is_email_available($email) {
        $count = $this->db
            ->where('email', $email)
            ->count_records($this->table_name);
        return (0==$count);
    }

    /**
     * Check to see whether a given email address has been registered to a 
     * login, for use in form validation.
     */
    public function is_email_registered($email) {
        return !($this->is_email_available($email));
    }

    /**
     * Check to see whether a password is correct, for use in form 
     * validator.
     */
    public function is_password_correct($valid, $field)
    {
        $login_name = (isset($valid['login_name'])) ?
            $valid['login_name'] : AuthProfiles::get_login('login_name');
        $count = $this->db
            ->where('login_name', $login_name)
            ->where('password', $this->encrypt_password($valid[$field]))
            ->count_records($this->table_name);
        return !(0===$count);
    }

    /**
     * Check whether the given password token is valid.
     *
     * @param  string  password reset token
     * @return boolean 
     */
    public function is_password_reset_token_valid($token)
    {
        $count = $this->db
            ->where('token', $token)
            ->count_records($this->_table_name_password_reset_token);
        return !(0==$count);
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

}
