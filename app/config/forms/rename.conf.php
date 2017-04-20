<?php
return [
    'method' => 'post',
    'elements' => [

        'path' => [
            'text',
            [
                'label' => _('File Name'),
                'description' => _('The relative path of the file in the station\'s media directory.'),
            ],
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => _('Save Changes'),
                'helper' => 'formButton',
                'class' => 'ui-button btn-lg btn-primary',
            ]
        ],

    ],
];