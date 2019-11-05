<?php

use App\Entity\StationPlaylist;

/** @var \App\Customization $customization */

return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'tabs' => [
        'info' => __('Basic Information'),
        'source' => __('Source'),
        'scheduling' => __('Scheduling'),
    ],

    'groups' => [


        'type_' . StationPlaylist::TYPE_SCHEDULED => [
            'use_grid' => true,
            'class' => 'type_fieldset',
            'tab' => 'scheduling',

            'elements' => [


            ],
        ],


        'grp_submit' => [
            'elements' => [

                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Save Changes'),
                        'class' => 'ui-button btn-lg btn-primary',
                    ],
                ],

            ],
        ],
    ],
];
