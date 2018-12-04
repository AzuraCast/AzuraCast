<?php
return [
    'method' => 'post',
    'elements' => [

        'new_file' => [
            'text',
            [
                'label' => __('File Name'),
                'description' => __('The relative path of the file in the station\'s media directory.'),
            ],
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Save Changes'),
                'class' => 'ui-button btn-lg btn-primary',
            ]
        ],

    ],
];
