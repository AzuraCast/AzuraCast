<?php
/** @var array $all_stations */

$form_config = [
    'method' => 'post',
    'elements' => [

        'name' => [
            'text',
            [
                'label' => __('Role Name'),
                'class' => 'half-width',
                'required' => true,
            ],
        ],

        'actions_global' => [
            'multiSelect',
            [
                'label' => __('System-Wide Permissions'),
                'choices' => $actions['global'],
                'class' => 'permission-select',
            ],
        ],

    ],
];

foreach ($all_stations as $station) {
    $form_config['elements']['actions_' . $station['id']] = [
        'multiSelect',
        [
            'label' => __('Permissions for %s', $station['name']),
            'choices' => $actions['station'],
            'class' => 'permission-select',
        ],
    ];
}

$form_config['elements']['submit'] = [
    'submit',
    [
        'type' => 'submit',
        'label' => __('Save Changes'),
        'class' => 'btn btn-lg btn-primary',
    ],
];

return $form_config;
