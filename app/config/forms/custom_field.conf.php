<?php

return [
    'method' => 'post',
    'elements' => [

        'name' => [
            'text',
            [
                'label' => __('Field Name'),
                'description' => __('This will be used as the label when editing individual songs, and will show in API results.'),
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