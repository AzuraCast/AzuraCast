<?php 
return array(
    'elements' => array(

        'long_url' => array('text', array(
            'label'     => 'Original URL (Target)',
            'description' => 'The URL you would like the short address to redirect to.',
            'required'  => true,
            'maxlength' => 300,
            'class'     => 'full-width',
            'placeholder' => 'http://full-url.here.com/',
        )),

        'short_url' => array('text', array(
            'label'     => 'Short URL',
            'maxlength' => 50,
            'description' => 'If you want to specify the short URL, enter it in this field. Leave this field blank to automatically generate one.',
            'class'     => 'half-width',
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'ui-button',
        )),

    ),
);