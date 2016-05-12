<?php
/**
 * Add Action Form
 */

return array(   
    /**
     * Form Configuration
     */
    'form' => array(
        'method'        => 'post',
        'elements'      => array(
                    
            'name'      => array('text', array(
                'label' => 'Action Name',
                'class' => 'half-width',
                'required' => true,
            )),
            
            'submit'        => array('submit', array(
                'type'  => 'submit',
                'label' => 'Save Changes',
                'helper' => 'formButton',
                'class' => 'btn btn-lg btn-primary',
            )),
        ),
    ),
);