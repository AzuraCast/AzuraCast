<?php
/**
 * Block Form
 */

return array(   
    /**
     * Form Configuration
     */
    'form' => array(
        'method'        => 'post',
        'elements'      => array(
            
            'name'      => array('text', array(
                'label' => 'Programmatic Name',
                'class' => 'full-width',
                'required' => true,
                'description' => 'The name used by the application to refer to this content block. Can be any string, but should be short and simple.',
            )),

            'url' => array('text', array(
                'label' => 'Standalone URL (Optional)',
                'description' => 'Enter the URL that corresponds to this content block, without leading slash.',
                'class' => 'half-width',
            )),
            
            'title'     => array('text', array(
                'label' => 'Title',
                'class' => 'full-width',
                'description' => 'To hide the title, leave this field blank.',
            )),
            
            'content'   => array('textarea', array(
                'label' => 'Content',
                'id'    => 'textarea-content',
                'description' => '<b>If pasting from Word:</b> paste the text into Notepad first, or you will encounter unexpected formatting problems.',
            )),
            
            'submit_btn' => array('submit', array(
                'type'  => 'submit',
                'label' => 'Save Changes',
                'helper' => 'formButton',
                'class' => 'ui-button',
            )),
        ),
    ),
);