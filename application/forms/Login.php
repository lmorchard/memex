<?php
/**
 * Login form
 */
class Memex_Form_Login extends Zend_Form
{
    public function init()
    {
        $username = $this->addElement('text', 'login_name', array(
            'filters'    => array('StringTrim', 'StringToLower'),
            'validators' => array(
                array('Alnum', true, array(false)),
                array('StringLength', false, array(3, 64))
            ),
            'required'   => true,
            'label'      => 'Login name:',
        ));

        $password = $this->addElement('password', 'password', array(
            'filters'    => array('StringTrim'),
            'validators' => array(
                array('StringLength', false, array(3, 64))
            ),
            'required'   => true,
            'label'      => 'Password:',
        ));

        $login = $this->addElement('submit', 'login', array(
            'required' => false,
            'ignore'   => true,
            'label'    => 'Login',
        ));

        // We want to display a 'failed authentication' message if necessary;
        // we'll do that with the form 'description', so we need to add that
        // decorator.
        $this->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'dl', 'class' => 'zend_form')),
            array('Description', array('placement' => 'prepend')),
            'Form'
        ));
    }
}
