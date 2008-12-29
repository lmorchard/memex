<?php
/**
 * Bookmark posting form.
 */
class Memex_Form_Post extends Zend_Form
{
    public function init()
    {
        // $helper = Zend_Controller_Action_HelperBroker::getStaticHelper('getModel');
        
        $this
            ->addElementPrefixPath(
                'Memex_Validate', APPLICATION_PATH . '/models/Validate/', 
                'validate'
            )
            ->addElementPrefixPath(
                'Memex_Filter', APPLICATION_PATH . '/models/Filter',
                'filter'
            )
            
            ->addElement('text', 'url', array(
                'label'      => 'URL',
                'required'   => true,
                'filters'    => array('StringTrim', 'NormalizeUrl'),
                'validators' => array('Uri')
            ));

        if (!$this->getAttrib('have_url')) {

            // If the form doesn't have a URL value yet, force into GET and 
            // omit the rest of the form.
            $this->setMethod('get')
                ->addElement('submit', 'save', array(
                    'label' => 'Next'
                ));

        } else {

            // Once a URL has been supplied, build the rest of the POST form.
            $this->setMethod('post')
                ->addElement('hash', 'csrf', array(
                    'salt' => Zend_Registry::get('config')->form->salt,
                    'decorators' => array( 
                        array('ViewHelper')
                    )
                ))
                ->addElement('text', 'title', array(
                    'label'      => 'Title',
                    'required'   => true,
                    'filters'    => array('StringTrim'),
                    'validators' => array(
                        array('StringLength', true, array(0, 255))
                    )
                ))
                ->addElement('textarea', 'notes', array(
                    'attribs'    => array(
                        'rows' => '5',
                        'cols' => '50'
                    ),
                    'label'      => 'Notes',
                    'required'   => false,
                    'validators' => array(
                        array('StringLength', true, array(0, 1024))
                    )
                ))
                ->addElement('text', 'tags', array(
                    'label'      => 'Tags',
                    'required'   => false,
                    'filters'    => array('StringTrim'),
                    'validators' => array(
                    )
                ))
                ->addElement('checkbox', 'private', array(
                    'label' => 'Private'
                ))
                /* TODO: Variable visibility?
                ->addElement('radio', 'visibility', array(
                    'label'    => 'Share',
                    'required' => true,
                    'attribs' => array(
                        'value'    => 1,
                    ),
                    'multiOptions' => array(
                        '1' => 'public',
                        '2' => 'friends-only',
                        '0' => 'private',
                    )
                ));
                 */

                ->addElement('submit', 'save', array(
                    'label' => 'save'
                ))
                ->addElement('submit', 'cancel', array(
                    'label' => 'cancel'
                ));

        }

        $this
            ->addElement('hidden', 'uuid', array(
                'decorators' => array('ViewHelper')
            )) 
            ->addElement('hidden', 'v', array(
                'decorators' => array('ViewHelper')
            )) 
            ->addElement('hidden', 'noui', array(
                'decorators' => array('ViewHelper')
            ))
            ->addElement('hidden', 'jump', array(
                'decorators' => array('ViewHelper')
            ))
            ->addElement('hidden', 'src', array(
                'decorators' => array('ViewHelper')
            ));

    }
}
