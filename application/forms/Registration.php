<?php
/**
 * Registration form
 */
class Memex_Form_Registration extends Zend_Form
{
    public function init()
    {
        $helper = Zend_Controller_Action_HelperBroker::getStaticHelper('getModel');

        $this->setMethod('post')
            ->addElementPrefixPath(
                'Memex_Validate', APPLICATION_PATH . '/models/Validate/', 
                'validate'
            );

        $this
            ->addElement('text', 'login_name', array(
                'label'      => 'Login name',
                'required'   => true,
                'filters'    => array('StringTrim'),
                'validators' => array(
                    array('StringLength', false, array(3, 64)),
                    array('Alnum', true, array(false)),
                    array('LoginNameAvailable', false, array($helper->getModel('Logins')))
                )
            ))
            ->addElement('text', 'email', array(
                'label'      => 'Email address',
                'required'   => true,
                'filters'    => array('StringTrim'),
                'validators' => array(
                    array('EmailAddress')
                )
            ))
            ->addElement('password', 'password', array(
                'label'      => 'Password',
                'required'   => true,
                'filters'    => array('StringTrim'),
                'validators' => array(
                    array('StringLength', false, array(3, 64)),
                    array('PasswordStrength')
                )
            ))
            ->addElement('password', 'password_confirm', array(
                'label'      => 'Password (confirm)',
                'required'   => true,
                'filters'    => array('StringTrim'),
                'validators' => array(
                    array('MatchField', false, array('password'))
                )
            ));

        $this->addDisplayGroup(
            array('login_name', 'email', 'password', 'password_confirm'), 
            'login',
            array('legend' => 'Login details')
        );

        $this
            ->addElement('text', 'screen_name', array(
                'label'      => 'Screen name',
                'required'   => true,
                'filters'    => array('StringTrim', 'StringToLower'),
                'validators' => array(
                    array('Alnum', false, array(false)),
                    array('StringLength', true, array(3, 64)),
                    array('ScreenNameAvailable', false, array($helper->getModel('Profiles')))
                )
            ))
            ->addElement('text', 'full_name', array(
                'label'      => 'Full name',
                'required'   => true,
                'filters'    => array('StringTrim'),
                'validators' => array(
                    array('StringLength', false, array(3, 128))
                )
            ))
            ->addElement('textarea', 'bio', array(
                'attribs'    => array(
                    'rows' => '5',
                    'cols' => '50'
                ),
                'label'      => 'Bio / About you',
                'required'   => false,
                'filters'    => array('StringTrim', 'StripTags'),
                'validators' => array(
                    array('StringLength', false, array(0, 1024))
                )
            ));

        $this->addDisplayGroup(
            array('screen_name', 'full_name', 'bio'), 
            'account',
            array('legend' => 'Profile details')
        );

        $this
            ->addElement('captcha', 'captcha', array(
                'label'      => 'Please enter the 5 letters displayed below:',
                'required'   => true,
                'captcha'    => array(
                    'captcha' => 'Figlet', 'wordLen' => 5, 'timeout' => 300
                )
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Register'
            ));

        $this->addDisplayGroup(
            array('captcha', 'submit'), 
            'finish',
            array('legend' => 'Register')
        );

        /* TODO: Work with this to make ul/li form, or give in and try styling dl/dt/dd
         
        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            'Description',
            array(array('data'=>'HtmlTag'),array('tag'=>'span')),
            array('Label',array()),
            array(array('row'=>'HtmlTag'),array('tag'=>'li'))
        ));

        $this->setDisplayGroupDecorators(array(
            'FormElements',
            array(array('data'=>'HtmlTag'),array('tag'=>'ul')),
            'FieldSet',
            array(array('row'=>'HtmlTag'),array('tag'=>'li'))
        ));

        $this->setDecorators(array(
            'FormElements',
            array(array('data'=>'HtmlTag'),array('tag'=>'ul')),
            'Form'
        ));

        */

        $this->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'dl', 'class' => 'zend_form')),
            array('Description', array('placement' => 'prepend')),
            'Form'
        ));

        parent::init();
    }

}
