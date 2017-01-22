<?php

return [
    'method' => 'post',
    'elements' => [

        'owner' => [
            'text',
            [
                'label' => _('API Key Owner'),
                'class' => 'half-width',
                'required' => true,
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => _('Save Changes'),
                'helper' => 'formButton',
                'class' => 'btn btn-lg btn-primary',
            ]
        ],
    ],
];