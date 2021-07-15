<?php

use App\Form\StationCloneForm;

return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [

        'profile' => [
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('New Station Name'),
                        'class' => 'half-width',
                        'required' => true,
                    ],
                ],

                'description' => [
                    'textarea',
                    [
                        'label' => __('New Station Description'),
                        'class' => 'full-width full-height',
                    ],
                ],

            ],
        ],

        'cloning' => [
            'use_grid' => true,
            'legend' => __('Customize Station Cloning'),
            'elements' => [

                'clone' => [
                    'checkboxes',
                    [
                        'label' => __('Copy to New Station:'),
                        'choices' => [
                            StationCloneForm::CLONE_MEDIA_STORAGE => __('Share Media Storage Location'),
                            StationCloneForm::CLONE_RECORDINGS_STORAGE => __('Share Recordings Storage Location'),
                            StationCloneForm::CLONE_PODCASTS_STORAGE => __('Share Podcasts Storage Location'),
                            StationCloneForm::CLONE_PLAYLISTS => __('Playlists'),
                            StationCloneForm::CLONE_MOUNTS => __('Mount Points'),
                            StationCloneForm::CLONE_REMOTES => __('Remote Relays'),
                            StationCloneForm::CLONE_STREAMERS => __('Streamers/DJs'),
                            StationCloneForm::CLONE_PERMISSIONS => __('User Permissions'),
                            StationCloneForm::CLONE_WEBHOOKS => __('Web Hooks'),
                        ],
                        'form_group_class' => 'col-sm-12',
                    ],
                ],

            ],
        ],

        'submit_grp' => [
            'elements' => [
                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Create New Station'),
                        'class' => 'btn btn-lg btn-primary',
                    ],
                ],
            ],
        ],
    ],
];
