<?php
/**
 * @var array $triggers
 * @var App\Environment $environment
 * @var App\Http\Router $router
 */

return [
    'method' => 'post',

    'groups' => [

        'api_info' => [
            'use_grid' => true,
            'legend' => __('Twitter Account Details'),
            'legend_class' => 'd-none',
            'description' => __(
                'Steps for configuring a Twitter application:<br>
                <ol type="1">
                    <li>Create a new app on the <a href="%s" target="_blank">Twitter Applications site</a>.
                    Use this installation\'s base URL as the application URL.</li>
                    <li>In the newly created application, click the "Keys and Access Tokens" tab.</li>
                    <li>At the bottom of the page, click "Create my access token".</li>
                </ol>
                <p>Once these steps are completed, enter the information from the "Keys and Access Tokens" page into the fields below.</p>',
                'https://developer.twitter.com/en/apps'
            ),

            'elements' => [

                'consumer_key' => [
                    'text',
                    [
                        'label' => __('Consumer Key (API Key)'),
                        'belongsTo' => 'config',
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'consumer_secret' => [
                    'text',
                    [
                        'label' => __('Consumer Secret (API Secret)'),
                        'belongsTo' => 'config',
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'token' => [
                    'text',
                    [
                        'label' => __('Access Token'),
                        'belongsTo' => 'config',
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'token_secret' => [
                    'text',
                    [
                        'label' => __('Access Token Secret'),
                        'belongsTo' => 'config',
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'rate_limit' => [
                    'select',
                    [
                        'label' => __('Only Send One Tweet Every...'),
                        'belongsTo' => 'config',
                        'default' => 0,
                        'choices' => [
                            0 => __('No Limit'),
                            15 => __('%d seconds', 15),
                            30 => __('%d seconds', 30),
                            60 => __('%d seconds', 60),
                            120 => __('%d minutes', 2),
                            300 => __('%d minutes', 5),
                            600 => __('%d minutes', 10),
                            900 => __('%d minutes', 15),
                            1800 => __('%d minutes', 30),
                            3600 => __('%d minutes', 60),
                        ],
                        'form_group_class' => 'col-sm-12',
                    ],
                ],

            ],
        ],

        'message_grp' => [
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

                'triggers' => [
                    'multiCheckbox',
                    [
                        'label' => __('Web Hook Triggers'),
                        'options' => $triggers,
                        'required' => true,
                        'form_group_class' => 'col-sm-12',
                    ],
                ],

                'message' => [
                    'textarea',
                    [
                        'label' => __('Message Body'),
                        'belongsTo' => 'config',
                        'required' => true,
                        'default' => __(
                            'Now playing on %s: %s by %s! Tune in now: %s',
                            '{{ station.name }}',
                            '{{ now_playing.song.title }}',
                            '{{ now_playing.song.artist }}',
                            '{{ station.public_player_url }}'
                        ),
                        'description' => __(
                            'Variables are in the form of <code>{{ var.name }}</code>. All values in the <a href="%s" target="_blank">Now Playing API response</a> are avaliable for use. Any empty fields are ignored.',
                            $router->named('api:nowplaying:index')
                        ),
                        'form_group_class' => 'col-sm-12',
                    ],
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
                    ],
                ],

            ],
        ],
    ],
];
