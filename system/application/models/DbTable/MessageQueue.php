<?php
/**
 * This is the DbTable class for the MessageQueue table.
 *
 * @author l.m.orchard <l.m.orchard@pobox.com>
 * @package Memex
 */
class Memex_Db_Table_MessageQueue extends Zend_Db_Table_Abstract
{
    /** Table name */
    protected $_name     = 'message_queue';
    protected $_rowClass = 'Memex_Db_Table_Row_MessageQueue';

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
        $u = new UUID();
        $data['uuid'] = $u->toRFC4122String();
        $data['created'] = date('Y-m-d\TH:i:sP', time());
        $data['modified'] = date('Y-m-d\TH:i:sP', time());
        return parent::insert($data);
    }

    /**
     * Update a row, maintaining the modified timestamp
     */
    public function update(array $data, $where)
    {
        $data['modified'] = date('Y-m-d\TH:i:sP', time());
        return parent::update($data, $where);
    }

    /**
     * Lock the table for read/write.
     */
    public function lock()
    {
        $db = $this->getAdapter();
        $adapter_name = strtolower(get_class($db));
        if (strpos($adapter_name, 'mysql') !== false) {
            $db->getConnection()->exec(
                "LOCK TABLES {$this->_name} WRITE, ".
                // HACK: Throw in a few aliased locks for subqueries.
                "{$this->_name} AS l1 WRITE, ".
                "{$this->_name} AS l2 WRITE, ".
                "{$this->_name} AS l3 WRITE"
            );
        }
    }

    public function unlock()
    {
        $db = $this->getAdapter();
        $adapter_name = strtolower(get_class($db));
        if (strpos($adapter_name, 'mysql') !== false) {
            $db->getConnection()->exec('UNLOCK TABLES'); 
        }
    }

}

/**
 * Wrapper for login rows.
 */
class Memex_Db_Table_Row_MessageQueue extends Zend_Db_Table_Row
{

    /**
     * Ensure all dates from database are represented as ISO8601
     */
    public function toArray()
    {
        $data = parent::toArray();
        foreach (array('created', 'modified', 'scheduled_for',
            'reserved_at', 'reserved_until', 'finished_at') as $key) {
            if (!empty($data[$key]))
                $data[$key] = date('c', strtotime($data[$key]));
        }
        return $data;
    }

}
