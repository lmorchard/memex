<?php
/**
 * Base class for models
 */
abstract class Memex_Model
{
    /** @var array Object registry */
    protected $_objects = array(
        'dbtable' => array(),
        'model'   => array(),
        'form'    => array()
    );

    /**
     * Public constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Conventional initializer method.
     */
    public function init()
    {
    }

    /**
     * Generate a UUID.
     *
     * @param string prefix for UUID
     */
    public function uuid($prefix='')
    {
        $chars = md5(uniqid(mt_rand(), true));
        return $prefix . join('-', array(
            substr($chars,0,8),
            substr($chars,8,4),
            substr($chars,12,4),
            substr($chars,16,4),
            substr($chars,20,12)
        ));
    }

    /**
     * Instantiate a table by name
     *
     * @param string Table name
     * @return Zend_Db_Table
     */
    public function getDbTable($name=null)
    {
        if (null == $name) $name = $this->_table_name;
        return $this->_loadResource($name, 'dbtable', 'models/DbTable', 'Memex_Db_Table');
    }

    /**
     * Instantiate a model by name
     *
     * @param string Model name
     * @return Decafbad_Model
     */
    public function getModel($name)
    {
        return $this->_loadResource($name, 'model', 'models', 'Memex_Model');
    }

    /**
     * Instantiate a form by name
     *
     * @param string Form name
     * @return Zend_Form
    public function getForm($name)
    {
        return $this->_loadResource($name, 'form', 'forms', 'Memex_Form');
    }
     */

    /**
     * Utility function to load a resource.
     */
    private function _loadResource($name, $type, $path, $class_prefix)
    {
        if (!isset($this->_objects[$type][$name])) {
            require_once APPLICATION_PATH . '/' . $path . '/' . $name . '.php';
            $class = $class_prefix . '_' . $name;
            $this->_objects[$type][$name] = new $class;
        }
        return $this->_objects[$type][$name];
    }

}
