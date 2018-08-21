<?php
/** @var array $app_settings */
/** @var array $triggers */
/** @var \App\Http\Router $router */

return [
    'method' => 'post',

    'groups' => [

        'api_info' => [
            'legend' => __('Web Hook Details'),
            'description' => sprintf(__('Web hooks automatically send a HTTP POST request to the URL you specify to 
                notify it any time one of the triggers you specify occurs on your station. The body of the POST message
                is the exact same as the <a href="%s" target="_blank">Now Playing API response</a> for your station. 
                In order to process quickly, web hooks have a short timeout, so the responding service should be
                optimized to handle the request in under 2 seconds.'),
                $router->named('api:nowplaying:index')),

            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('%s Name', __('Web Hook')),
                        'description' => __('Choose a name for this webhook that will help you distinguish it from others. This will only be shown on the administration page.'),
                        'required' => true,
                    ]
                ],

                'webhook_url' => [
                    'url',
                    [
                        'label' => __('Web Hook URL'),
                        'description' => __('The URL that will receive the POST messages any time an event is triggered.'),
                        'belongsTo' => 'config',
                        'required' => true,
                    ]
                ],

                'triggers' => [
                    'multiCheckbox',
                    [
                        'label' => __('Web Hook Triggers'),
                        'options' => $triggers,
                        'required' => true,
                    ]
                ],

            ],
        ],

        'submit_grp' => [
            'elements' => [

                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Save Changes'),
                        'class' => 'ui-button btn-lg btn-primary',
                    ]
                ],

            ]
        ]
    ],
];
