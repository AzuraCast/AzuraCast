<?php
/** @var array $all_stations */

$actions = \App\Acl::listPermissions();

$form_config = [
    'method' => 'post',
    'groups' => [

        'basic_info' => [
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('Role Name'),
                        'class' => 'half-width',
                        'required' => true,
                    ]
                ],

            ],
        ],

        'grp_global' => [
            'legend' => __('System-Wide Permissions'),
            'elements' => [

                'actions_global' => [
                    'multiSelect',
                    [
                        'label' => __('Actions'),
                        'choices' => $actions['global'],
                    ]
                ],

            ],
        ],

    ],
];

foreach ($all_stations as $station) {
    $form_config['groups']['grp_station_' . $station['id']] = [
        'legend' => __('Per-Station').': '.$station['name'],
        'elements' => [

            'actions_' . $station['id'] => [
                'multiSelect',
                [
                    'label' => __('Actions'),
                    'choices' => $actions['station'],
                ]
            ],

        ],
    ];
}

$form_config['groups']['grp_submit'] = [
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
];

return $form_config;
