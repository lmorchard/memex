<?php
/**
 * Validator to assert that this field should match another, mainly for 
 * password confirmation.
 *
 * see: http://framework.zend.com/manual/en/zend.form.elements.html
 */
class Memex_Validate_MatchField extends Zend_Validate_Abstract
{
    const NOT_MATCH = 'notMatch';

    protected $_match_field;

    protected $_messageTemplates = array(
        self::NOT_MATCH => 'Fields do not match'
    );

    public function __construct($match_field)
    {
        $this->_match_field = $match_field;
    }

    public function isValid($value, $context = null)
    {
        $value = (string) $value;
        $this->_setValue($value);

        if (is_array($context)) {
            if (isset($context[$this->_match_field])
                && ($value == $context[$this->_match_field]))
            {
                return true;
            }
        } elseif (is_string($context) && ($value == $context)) {
            return true;
        }

        $this->_error(self::NOT_MATCH);
        return false;
    }
}
