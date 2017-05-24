<?php
/**
 * Edit Role Form
 */

/** @var array */
$config = $di['config'];
$actions = $config->actions->toArray();

/** @var \Doctrine\ORM\EntityManager $em */
$em = $di['em'];
$all_stations = $em->getRepository(\Entity\Station::class)->fetchArray();

$form_config = [
    'method' => 'post',
    'groups' => [

        'basic_info' => [
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => _('Role Name'),
                        'class' => 'half-width',
                        'required' => true,
                    ]
                ],

            ],
        ],

        'grp_global' => [
            'legend' => _('System-Wide Permissions'),
            'elements' => [

                'actions_global' => [
                    'multiSelect',
                    [
                        'label' => _('Actions'),
                        'multiOptions' => array_combine(array_values($actions['global']), array_values($actions['global'])),
                    ]
                ],

            ],
        ],

    ],
];

foreach ($all_stations as $station) {
    $form_config['groups']['grp_station_' . $station['id']] = [
        'legend' => _('Per-Station').': '.$station['name'],
        'elements' => [

            'actions_' . $station['id'] => [
                'multiSelect',
                [
                    'label' => _('Actions'),
                    'multiOptions' => array_combine(array_values($actions['station']), array_values($actions['station'])),
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
                'label' => _('Save Changes'),
                'class' => 'btn btn-lg btn-primary',
            ]
        ],
    ],
];

return $form_config;