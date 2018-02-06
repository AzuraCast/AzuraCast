<?php

return [
    'method' => 'post',
    'elements' => [

        'comment' => [
            'text',
            [
                'label' => _('Comments'),
                'description' => _('Describe the use-case for this API key for future reference.'),
                'class' => 'half-width',
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => _('Save Changes'),
                'class' => 'btn btn-lg btn-primary',
            ]
        ],
    ],
];