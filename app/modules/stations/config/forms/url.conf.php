<?php
return [
    'elements' => [

        'long_url' => ['text', [
            'label' => 'Original URL (Target)',
            'description' => 'The URL you would like the short address to redirect to.',
            'required' => true,
            'maxlength' => 300,
            'class' => 'full-width',
            'placeholder' => 'http://full-url.here.com/',
        ]],

        'short_url' => ['text', [
            'label' => 'Short URL',
            'maxlength' => 50,
            'description' => 'If you want to specify the short URL, enter it in this field. Leave this field blank to automatically generate one.',
            'class' => 'half-width',
        ]],

        'submit' => ['submit', [
            'type' => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'btn btn-lg btn-primary',
        ]],

    ],
];