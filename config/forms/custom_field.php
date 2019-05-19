<?php

return [
    'method' => 'post',
    'groups' => [
        [
            'use_grid' => true,
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('Field Name'),
                        'description' => __('This will be used as the label when editing individual songs, and will show in API results.'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'short_name' => [
                    'text',
                    [
                        'label' => __('Programmatic Name'),
                        'description' => __('Optionally specify an API-friendly name, such as <code>field_name</code>. Leave this field blank to automatically create one based on the name.'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Save Changes'),
                        'class' => 'btn btn-lg btn-primary',
                        'form_group_class' => 'col-sm-12',
                    ]
                ],

            ]
        ]
    ]
];
