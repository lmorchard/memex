<?php
require_once dirname(__FILE__) . '/LoginsProfiles.php';

/**
 * Table data gateway for profiles table
 */
class Memex_Db_Table_Profiles extends Zend_Db_Table_Abstract
{
    /** Table name */
    protected $_name     = 'profiles';
    protected $_rowClass = 'Memex_Db_Table_Row_Profiles';

    /**
     * Insert new row
     *
     * Ensure that a timestamp is set for the created field.
     * 
     * @param  array $data 
     * @return int
     */
    public function insert(array $data)
    {
        $data['created'] = date('Y-m-d\TH:i:sP', time());
        return parent::insert($data);
    }

}

/**
 * Wrapper for login rows.
 */
class Memex_Db_Table_Row_Profiles extends Zend_Db_Table_Row
{

}
