<?php
/**
 * This is the DbTable class for the urls table.
 */
class Memex_Db_Table_Urls extends Zend_Db_Table_Abstract
{
    /** Table name */
    protected $_name     = 'urls';
    protected $_rowClass = 'Memex_Db_Table_Row_Urls';
    
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
class Memex_Db_Table_Row_Urls extends Zend_Db_Table_Row
{

}
