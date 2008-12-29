<?php
/**
 * Validator to assert unique login name
 *
 * TODO: refactor / merge with screen name validator?
 *
 * see: http://framework.zend.com/manual/en/zend.validate.writing_validators.html
 */
class Memex_Validate_LoginNameAvailable extends Zend_Validate_Abstract
{
    const NOT_AVAILABLE = 'not_available';

    protected $_messageTemplates = array(
        self::NOT_AVAILABLE => "'%value%' is not available as a unique login name"
    );

    protected $_model;

    public function __construct($model)
    {
        $this->_model = $model;
    }

    public function isValid($value)
    {
        $this->_setValue($value);

        $is_valid = true;

        if ($this->_model->fetchByLoginName($value)) {
            $this->_error(self::NOT_AVAILABLE);
            $is_valid = false;
        }

        return $is_valid;
    }

}
