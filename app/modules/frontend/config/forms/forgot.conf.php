<?php
/**
 * Forgot Password Form
 */

return array(
    'method'        => 'post',
    'elements'      => array(

        'contact_email' => array('text', array(
            'label' => 'E-mail Address',
            'class' => 'half-width',
            'required' => true,
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Send Recovery Code',
            'helper' => 'formButton',
            'class' => 'ui-button',
        )),
    ),
);