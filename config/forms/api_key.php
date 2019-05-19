<?php

return [
    'method' => 'post',
    'elements' => [

        'comment' => [
            'text',
            [
                'label' => __('Comments'),
                'description' => __('Describe the use-case for this API key for future reference.'),
                'class' => 'half-width',
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Save Changes'),
                'class' => 'btn btn-lg btn-primary',
            ]
        ],
    ],
];
