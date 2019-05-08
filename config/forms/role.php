<?php
/** @var array $all_stations */

$actions = \App\Acl::listPermissions();

$form_config = [
    'method' => 'post',
    'elements' => [

        'name' => [
            'text',
            [
                'label' => __('Role Name'),
                'class' => 'half-width',
                'required' => true,
                'label_class' => 'mb-2',
                'form_group_class' => 'col-sm-12 mt-3',
            ]
        ],

        'actions_global' => [
            'multiSelect',
            [
                'label' => __('System-Wide Permissions'),
                'choices' => $actions['global'],
                'class' => 'permission-select',
                'label_class' => 'mb-2',
                'form_group_class' => 'col-sm-12 mt-3',
            ]
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
            'label_class' => 'mb-2',
            'form_group_class' => 'col-sm-12 mt-3',
        ]
    ];
}

$form_config['elements']['submit'] = [
    'submit',
    [
        'type' => 'submit',
        'label' => __('Save Changes'),
        'class' => 'btn btn-lg btn-primary',
        'form_group_class' => 'col-sm-12 mt-3',
    ]
];

return $form_config;
