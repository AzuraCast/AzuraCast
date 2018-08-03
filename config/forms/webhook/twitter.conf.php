<?php
return [
    'method' => 'post',

    'groups' => [

        'api_info' => [
            'legend' => __('Twitter Account Details'),
            'description' => sprintf(__('Steps for configuring a Twitter application:<br>
                <ol type="1">
                    <li>Create a new app on the <a href="%s" target="_blank">Twitter Applications site</a>. 
                    Use this installation\'s base URL as the application URL.</li>
                    <li>In the newly created application, click the "Keys and Access Tokens" tab.</li>
                    <li>At the bottom of the page, click "Create my access token".</li>
                </ol>
                Once these steps are completed, enter the information from the "Keys and Access Tokens" page into the fields below.'),
                'https://apps.twitter.com/'),

            'elements' => [

                'consumer_key' => [
                    'text',
                    [
                        'label' => __('Consumer Key (API Key)'),
                        'belongsTo' => 'config',
                        'required' => true,
                    ]
                ],

                'consumer_secret' => [
                    'text',
                    [
                        'label' => __('Consumer Secret (API Secret)'),
                        'belongsTo' => 'config',
                        'required' => true,
                    ]
                ],

                'token' => [
                    'text',
                    [
                        'label' => __('Access Token'),
                        'belongsTo' => 'config',
                        'required' => true,
                    ]
                ],

                'token_secret' => [
                    'text',
                    [
                        'label' => __('Access Token Secret'),
                        'belongsTo' => 'config',
                        'required' => true,
                    ]
                ],

            ],
        ],

        'message_grp' => [
            'legend' => __('Web Hook Details'),
            'description' => sprintf(__('Variables are in the form of <code>{{ var.name }}</code>. All values in the <a href="%s" target="_blank">Now Playing API response</a> are avaliable for use. Any empty fields are ignored.'), $url->named('api:nowplaying:index')),

            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('%s Name', __('Web Hook')),
                        'description' => __('Choose a name for this webhook that will help you distinguish it from others. This will only be shown on the administration page.'),
                        'required' => true,
                    ]
                ],

                'triggers' => [
                    'multiCheckbox',
                    [
                        'label' => __('Web Hook Triggers'),
                        'options' => \App\Webhook\Dispatcher::getTriggers(),
                        'required' => true,
                    ]
                ],

                'message' => [
                    'textarea',
                    [
                        'label' => __('Message Body'),
                        'belongsTo' => 'config',
                        'required' => true,
                        'default' => sprintf(__('Now playing on %s: %s by %s! Tune in now.'), '{{ station.name }}', '{{ now_playing.song.title }}', '{{ now_playing.song.artist }}'),
                    ]
                ]

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
