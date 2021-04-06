<?php

return [
    'elements' => [

        'storage_location' => [
            'select',
            [
                'label' => __('Storage Location'),
                'choices' => $storageLocations,
            ],
        ],

        'path' => [
            'text',
            [
                'label' => __('Backup Filename'),
                'description' => __('This will be the file name for your backup, include the file type (.zip or .rar) you wish to use.'),
            ],
        ],

        'exclude_media' => [
            'toggle',
            [
                'label' => __('Exclude Media from Backup'),
                'description' => __('This will produce a significantly smaller backup, but you should make sure to back up your media elsewhere. Note that only locally stored media will be backed up.'),
                'selected_text' => __('Yes'),
                'deselected_text' => __('No'),
                'default' => false,
            ],
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Save Changes'),
                'class' => 'btn btn-lg btn-primary',
            ],
        ],

    ],
];
