<?php
require_once dirname(__FILE__) . '/LoginsProfiles.php';

/**
 * Table data gateway for logins table
 */
class Memex_Db_Table_Logins extends Zend_Db_Table_Abstract
{
    /** Table name */
    protected $_name     = 'logins';
    protected $_rowClass = 'Memex_Db_Table_Row_Logins';

    protected $_dependentTables = array( 'Memex_Db_Table_LoginsProfiles' );

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
 * Row data gateway for login
 */
class Memex_Db_Table_Row_Logins extends Zend_Db_Table_Row
{

}
