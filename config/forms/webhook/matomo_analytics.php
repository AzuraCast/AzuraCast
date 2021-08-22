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

                'matomo_url' => [
                    'url',
                    [
                        'label' => __('Matomo Installation Base URL'),
                        'description' => __('The full base URL of your Matomo installation.'),
                        'belongsTo' => 'config',
                        'required' => true,
                        'form_group_class' => 'col-md-12',
                    ],
                ],

                'site_id' => [
                    'text',
                    [
                        'label' => __('Matomo Site ID'),
                        'description' => __('The numeric site ID for this site.'),
                        'belongsTo' => 'config',
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'token' => [
                    'text',
                    [
                        'label' => __('Matomo API Token'),
                        'description' => __('Optionally supply an API token to allow IP address overriding.'),
                        'belongsTo' => 'config',
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
