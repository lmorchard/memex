<?php
/**
 * This is the DbTable class for the profiles table.
 *
 * @package    Memex
 * @subpackage models
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Profiles_Model extends Model
{
    protected $_table_name = 'profiles';

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
        if ($this->fetch_by_screen_name($data['screen_name']))
            throw new Exception('duplicate screen name');

        $data = array(
            'uuid'        => uuid::uuid(),
            'screen_name' => $data['screen_name'],
            'full_name'   => $data['full_name'],
            'bio'         => empty($data['bio']) ? '' : $data['bio'],
            'created'     => gmdate('c')
        );
        $data['id'] = $this->db
            ->insert('profiles', $data)
            ->insert_id();

        return $data;
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

        $profile = $this->db
            ->select()
            ->from('profiles')
            ->where('id', $data['id'])
            ->get()->current();

        $accepted_fields = array(
            'screen_name', 'full_name', 'bio'
        );
        foreach ($accepted_fields as $key) {
            if (isset($data[$key]))
                $profile[$key] = $data[$key];
        }
        $this->db->update(
            'profiles', $profile, array('id'=>$data['id'])
        );

        return $profile;
    }

    /**
     * Look up by id
     *
     * @param string profile id
     * @return array profile data
     */
    public function fetch_by_id($profile_id)
    {
        return $this->fetch_one_by($profile_id, null);
    }

    /**
     * Look up by screen name
     *
     * @param string Screen name
     * @return array profile data
     */
    public function fetch_by_screen_name($screen_name)
    {
        return $this->fetch_one_by(null, $screen_name);
    }

    /**
     * Look up by a variety of criteria
     *
     * @param string profile id
     * @param string Screen name
     * @return array profile data
     */
    public function fetch_one_by($id=null, $screen_name=null)
    {
        $select = $this->db->
            select()->from('profiles');
        if (null != $id)
            $select->where('id', $id);
        if (null != $screen_name)
            $select->where('screen_name', $screen_name);
        return $select->get()->current();
    }

    /**
     * Set a profile attribute
     *
     * @param string Profile ID
     * @param string Profile attribute name
     * @param string Profile attribute value
     */
    public function set_attribute($profile_id, $name, $value)
    {
        $row = $this->db
            ->select()->from('profile_attribs')
            ->where('profile_id', $profile_id)
            ->where('name', $name)
            ->get()->current();

        if (null == $row) {
            $data = array(
                'profile_id' => $profile_id,
                'name'       => $name,
                'value'      => $value
            );
            $data['id'] = $this->db
                ->insert('profile_attribs', $data)
                ->insert_id();
        } else {
            $this->db->update(
                'profile_attribs', 
                array('value' => $value),
                array('profile_id'=>$profile_id, 'name'=>$name)
            );
        }
    }

    /**
     * Set profile attributes
     *
     * @param string Profile ID
     * @param array list of profile attributes
     */
    public function set_attributes($profile_id, $attributes)
    {
        foreach ($attributes as $name=>$value) {
            $this->set_attribute($profile_id, $name, $value);
        }
    }

    /**
     * Get a profile attribute
     *
     * @param string Profile ID
     * @param string Profile attribute name
     * @return string Attribute value 
     */
    public function get_attribute($profile_id, $name)
    {
        $select = $this->db
            ->select('value')
            ->from('profile_attribs')
            ->where('profile_id', $profile_id)
            ->where('name', $name);
        $row = $select->get()->current();
        if (null == $row) return false;
        return $row['value'];
    }

    /**
     * Get all profile attributes
     *
     * @param string Profile ID
     * @return array Profile attributes
     */
    public function get_attributes($profile_id, $names=null)
    {
        $select = $this->db->select()
            ->from('profile_attribs')
            ->where('profile_id', $profile_id);
        if (null != $names) {
            $select->in('name', $names);
        }
        $rows = $select->get();
        $attribs = array();
        foreach ($rows as $row) {
            $attribs[$row['name']] = $row['value'];
        }
        return $attribs;
    }

    /**
     * Build and return a validator for a profile editing form
     *
     * @param array Form data to validate.
     */
    public function get_validator($data)
    {
        $valid = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('screen_name',      
                'required', 'length[3,64]', 'valid::alpha_dash', array($profiles_model, 'isScreenNameAvailable'))
            ->add_rules('full_name',        
                'required', 'valid::standard_text')
            ;
        return $valid;
    }

    /**
     *
     */
    public function is_screen_name_available($name)
    {
        $profile = $this->fetch_by_screen_name($name);
        return empty($profile);
    }

    /**
     * Delete all profiles from the system.  Useful for tests, but dangerous 
     * otherwise.
     */
    public function delete_all()
    {
        if (!Kohana::config('model.enable_delete_all'))
            throw new Exception('Mass deletion not enabled');
        $this->db->query('DELETE FROM ' . $this->_table_name);
        $this->db->query('DELETE FROM profile_attribs');
    }

}
