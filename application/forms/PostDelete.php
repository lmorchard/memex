<?php
/**
 * Bookmark posting form.
 */
class Memex_Form_PostDelete extends Zend_Form
{
    public function init()
    {
        $this
            ->addElementPrefixPath(
                'Memex_Validate', APPLICATION_PATH . '/models/Validate/', 
                'validate'
            )
            ->addElementPrefixPath(
                'Memex_Filter', APPLICATION_PATH . '/models/Filter',
                'filter'
            )
            ->setMethod('post')
            ->addElement('hash', 'csrf', array(
                'salt' => Zend_Registry::get('config')->form->salt,
                'decorators' => array( 
                    array('ViewHelper')
                )
            ))
            ->addElement('hidden', 'uuid', array(
                'decorators' => array('ViewHelper')
            ))
            ->addElement('submit', 'delete', array(
                'label' => 'delete'
            ))
            ->addElement('submit', 'cancel', array(
                'label' => 'cancel'
            ));
    }
}
