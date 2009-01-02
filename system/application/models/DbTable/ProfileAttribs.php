<?php
/**
 * Table data gateway for ProfileAttribs table
 */
class Memex_Db_Table_ProfileAttribs extends Zend_Db_Table_Abstract
{
    /** Table name */
    protected $_name     = 'profile_attribs';
    protected $_rowClass = 'Memex_Db_Table_Row_ProfileAttribs';
}

/**
 * Wrapper for ProfileAttribs rows.
 */
class Memex_Db_Table_Row_ProfileAttribs extends Zend_Db_Table_Row
{

}
