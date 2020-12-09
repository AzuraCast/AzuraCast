<?php
/**
 * @var array $triggers
 * @var App\Environment $environment
 * @var App\Http\Router $router
 */

return [
    'method' => 'post',

    'groups' => [
        [
            'use_grid' => true,
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('Web Hook Name'),
                        'description' => __(
                            'Choose a name for this webhook that will help you distinguish it from others. This will only be shown on the administration page.'
                        ),
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'station_id' => [
                    'text',
                    [
                        'label' => __('TuneIn Station ID'),
                        'description' => __('The station ID will be a numeric string that starts with the letter S.'),
                        'belongsTo' => 'config',
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'partner_id' => [
                    'text',
                    [
                        'label' => __('TuneIn Partner ID'),
                        'belongsTo' => 'config',
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'partner_key' => [
                    'text',
                    [
                        'label' => __('TuneIn Partner Key'),
                        'belongsTo' => 'config',
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Save Changes'),
                        'class' => 'ui-button btn-lg btn-primary',
                        'form_group_class' => 'col-sm-12',
                    ],
                ],

            ],
        ],
    ],
];
