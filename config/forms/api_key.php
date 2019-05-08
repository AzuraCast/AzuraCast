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
                'label_class' => 'mb-2',
                'form_group_class' => 'col-sm-12 mt-3',
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Save Changes'),
                'class' => 'btn btn-lg btn-primary',
                'form_group_class' => 'col-md-6 mt-3',
            ]
        ],
    ],
];
