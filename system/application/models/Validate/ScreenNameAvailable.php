<?php
/**
 * Validator to assert unique account screen name
 *
 * TODO: refactor / merge with login name validator?
 *
 * see: http://framework.zend.com/manual/en/zend.validate.writing_validators.html
 */
class Memex_Validate_ScreenNameAvailable extends Zend_Validate_Abstract
{
    const NOT_AVAILABLE = 'not_available';

    protected $_messageTemplates = array(
        self::NOT_AVAILABLE => "'%value%' is not available as a unique screen name"
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

        if ($this->_model->fetchByScreenName($value)) {
            $this->_error(self::NOT_AVAILABLE);
            $is_valid = false;
        }

        return $is_valid;
    }

}
