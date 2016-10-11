<?php
/**
 * Edit Role Form
 */

/** @var array */
$module_config = $di['module_config'];

$actions = [];

foreach($module_config as $module_name => $config_row)
{
    $module_actions = $config_row->actions->toArray();

    if (!empty($module_actions))
    {
        foreach($module_actions['global'] as $action_name)
            $actions['global'][$action_name] = $action_name;

        foreach($module_actions['station'] as $action_name)
            $actions['station'][$action_name] = $action_name;
    }
}

/** @var \Doctrine\ORM\EntityManager $em */
$em = $di['em'];
$all_stations = $em->getRepository(\Entity\Station::class)->fetchArray();

$form_config = [
    'method' => 'post',
    'groups' => [

        'basic_info' => [
            'elements' => [

                'name' => ['text', [
                    'label' => _('Role Name'),
                    'class' => 'half-width',
                    'required' => true,
                ]],

            ],
        ],

        'grp_global' => [
            'legend' => _('System-Wide Permissions'),
            'elements' => [

                'actions_global' => ['multiSelect', [
                    'label' => _('Actions'),
                    'multiOptions' => $actions['global'],
                ]],

            ],
        ],

    ],
];

foreach($all_stations as $station)
{
    $form_config['groups']['grp_station_'.$station['id']] = [
        'legend' => $station['name'],
        'elements' => [

            'actions_'.$station['id'] => ['multiSelect', [
                'label' => _('Actions'),
                'multiOptions' => $actions['station'],
            ]],

        ],
    ];
}

$form_config['groups']['grp_submit'] = [
    'elements' => [
        'submit' => ['submit', [
            'type' => 'submit',
            'label' => _('Save Changes'),
            'helper' => 'formButton',
            'class' => 'btn btn-lg btn-primary',
        ]],
    ],
];

return $form_config;