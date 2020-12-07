<?php

return [
    'groups' => [

        'backup' => [
            'use_grid' => true,

            'elements' => [

                'backupEnabled' => [
                    'toggle',
                    [
                        'label' => __('Run Automatic Nightly Backups'),
                        'description' => __('Enable to have AzuraCast automatically run nightly backups at the time specified.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'backupTimeCode' => [
                    'PlaylistTime',
                    [
                        'label' => __('Scheduled Backup Time'),
                        'description' => __('The time (in UTC) to run the automated backup, if enabled.'),
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'backupExcludeMedia' => [
                    'toggle',
                    [
                        'label' => __('Exclude Media from Backups'),
                        'description' => __('Excluding media from automated backups will save space, but you should make sure to back up your media elsewhere. Note that only locally stored media will be backed up.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'backupKeepCopies' => [
                    'number',
                    [
                        'label' => __('Number of Backup Copies to Keep'),
                        'description' => __('Copies older than the specified number of days will automatically be deleted. Set to zero to disable automatic deletion.'),
                        'min' => 0,
                        'max' => 365,
                        'default' => 0,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'backupStorageLocation' => [
                    'select',
                    [
                        'label' => __('Storage Location'),
                        'choices' => $storageLocations,
                        'form_group_class' => 'col-md-12',
                    ],
                ],

            ],
        ],

        'submit' => [
            'elements' => [
                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Save Changes'),
                        'class' => 'btn btn-lg btn-primary',
                    ],
                ],
            ],
        ],
    ],
];
