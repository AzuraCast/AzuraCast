<?php
/** @var \App\Url $url */
/** @var array $app_settings */

return [
    'method' => 'post',

    'groups' => [

        'api_info' => [
            'legend' => __('Discord API Details'),
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
                        'label' => __('Discord Web Hook URL'),
                        'description' => __('This URL is provided within the Discord application.'),
                        'belongsTo' => 'config',
                        'required' => true,
                    ]
                ],

                'triggers' => [
                    'multiCheckbox',
                    [
                        'label' => __('Web Hook Triggers'),
                        'options' => array_diff_key(\App\Webhook\Dispatcher::getTriggers(), ['listener_lost' => 1, 'listener_gained' => 1]),
                        'required' => true,
                    ]
                ],

            ],
        ],

        'message' => [
            'legend' => __('Customize Discord Message'),
            'description' => sprintf(__('Variables are in the form of <code>{{ var.name }}</code>. All values in the <a href="%s" target="_blank">Now Playing API response</a> are avaliable for use. Any empty fields are ignored.'), $url->named('api:nowplaying:index')),

            'elements' => [

                'content' => [
                    'text',
                    [
                        'label' => __('Main Message Content'),
                        'belongsTo' => 'config',
                        'default' => sprintf(__('Now playing on %s:'), '{{ station.name }}'),
                    ]
                ],

                'title' => [
                    'text',
                    [
                        'label' => __('Title'),
                        'belongsTo' => 'config',
                        'default' => '{{ now_playing.song.title }}',
                    ]
                ],

                'description' => [
                    'text',
                    [
                        'label' => __('Description'),
                        'belongsTo' => 'config',
                        'default' => '{{ now_playing.song.artist }}',
                    ]
                ],

                'url' => [
                    'text',
                    [
                        'label' => __('URL'),
                        'belongsTo' => 'config',
                        'default' => '{{ station.listen_url }}',
                    ]
                ],

                'author' => [
                    'text',
                    [
                        'label' => __('Author Name'),
                        'belongsTo' => 'config',
                        'default' => '{{ live.streamer_name }}',
                    ]
                ],

                'thumbnail' => [
                    'text',
                    [
                        'label' => __('Thumbnail Image URL'),
                        'belongsTo' => 'config',
                        'default' => '{{ now_playing.song.art }}',
                    ]
                ],

                'footer' => [
                    'text',
                    [
                        'label' => __('Footer Text'),
                        'belongsTo' => 'config',
                        'default' => sprintf(__('Powered by %s'), $app_settings['name']),
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
