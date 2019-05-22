<?php
use App\Entity;

return [
    'groups' => [

        'backup' => [
            'use_grid' => true,

            'elements' => [

                Entity\Settings::BACKUP_ENABLED => [
                    'toggle',
                    [
                        'label' => __('Run Automatic Nightly Backups'),
                        'description' => __('Enable to have AzuraCast automatically run nightly backups at the time specified.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                Entity\Settings::BACKUP_TIME => [
                    'PlaylistTime',
                    [
                        'label' => __('Scheduled Backup Time'),
                        'description' => __('The time (in UTC) to run the automated backup, if enabled.'),
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                Entity\Settings::BACKUP_EXCLUDE_MEDIA => [
                    'toggle',
                    [
                        'label' => __('Exclude Media from Backups'),
                        'description' => __('Excluding media from automated backups will save space, but you should make sure to back up your media elsewhere.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6',
                    ]
                ],

                Entity\Settings::BACKUP_KEEP_COPIES => [
                    'number',
                    [
                        'label' => __('Number of Backup Copies to Keep'),
                        'description' => __('Copies older than the specified number of days will automatically be deleted. Set to zero to disable automatic deletion.'),
                        'min' => 0,
                        'max' => 365,
                        'default' => 0,
                        'form_group_class' => 'col-md-6',
                    ]
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
                    ]
                ],
            ],
        ],
    ],
];
