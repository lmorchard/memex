<?php
/**
 * This is the DbTable class for the urls table.
 */
class Memex_Db_Table_Tags extends Zend_Db_Table_Abstract
{
    /** Table name */
    protected $_name     = 'tags';
    protected $_rowClass = 'Memex_Db_Table_Row_Tags';

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
        $data['modified'] = date('Y-m-d\TH:i:sP', time());
        return parent::insert($data);
    }

    /**
     * Update a row
     *
     * Ensure that a timestamp is set for the updated field.
     * 
     * @param  array $data 
     * @return int
     */
    public function update(array $data, $where)
    {
        $data['modified'] = date('Y-m-d\TH:i:sP', time());
        return parent::update($data, $where);
    }

}

/**
 * Wrapper for login rows.
 */
class Memex_Db_Table_Row_Tags extends Zend_Db_Table_Row
{

}
