<?php
/**
 * This is the DbTable class for the Posts table.
 */
class Memex_Db_Table_Posts extends Zend_Db_Table_Abstract
{
    /** Table name */
    protected $_name     = 'posts';
    protected $_rowClass = 'Memex_Db_Table_Row_Posts';

    protected $_referenceMap = array(
        'Url' => array(
            'columns'       => 'url_id',
            'refTableClass' => 'Urls',
            'refColumns'    => 'id'
        )
    );

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
        if (empty($data['user_date']))
            $data['user_date'] = date('Y-m-d\TH:i:sP', time());
        return parent::insert($data);
    }

}

/**
 * Wrapper for login rows.
 */
class Memex_Db_Table_Row_Posts extends Zend_Db_Table_Row
{

}
