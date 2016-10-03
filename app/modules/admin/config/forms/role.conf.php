<?php
/**
 * Edit Role Form
 */

/** @var \Doctrine\ORM\EntityManager $em */
$em = $di['em'];

$all_actions = $em->getRepository(\Entity\RoleHasAction::class)->getSelectableActions();

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
    ],
];

$form_config['groups']['grp_global'] = [
    'legend' => _('System-Wide Permissions'),
    'elements' => [

        'actions_global' => ['multiCheckbox', [
            'label' => _('Actions'),
            'multiOptions' => $all_actions['global'],
        ]],

    ],
];

foreach($all_actions['stations'] as $station_id => $station_info)
{
    $form_config['groups']['grp_station_'.$station_id] = [
        'legend' => $station_info['name'],
        'elements' => [

            'actions_'.$station_id => ['multiCheckbox', [
                'label' => _('Actions'),
                'multiOptions' => $station_info['actions'],
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