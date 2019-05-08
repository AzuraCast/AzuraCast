<?php
/** @var array $app_settings */
/** @var array $triggers */
/** @var \App\Http\Router $router */

return [
    'method' => 'post',

    'groups' => [

        'api_info' => [
            'legend' => __('Discord API Details'),
            'legend_class' => 'd-none',
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('%s Name', __('Web Hook')),
                        'description' => __('Choose a name for this webhook that will help you distinguish it from others. This will only be shown on the administration page.'),
                        'required' => true,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'webhook_url' => [
                    'url',
                    [
                        'label' => __('Discord Web Hook URL'),
                        'description' => __('This URL is provided within the Discord application.'),
                        'belongsTo' => 'config',
                        'required' => true,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'triggers' => [
                    'multiCheckbox',
                    [
                        'label' => __('Web Hook Triggers'),
                        'options' => array_diff_key($triggers, ['listener_lost' => 1, 'listener_gained' => 1]),
                        'required' => true,
                        'form_group_class' => 'col-sm-12 mt-1',
                    ]
                ],

            ],
        ],

        'message' => [
            'legend' => __('Customize Message'),
            'legend_class' => 'd-none',
            'description' => sprintf(__('Variables are in the form of <code>{{ var.name }}</code>. All values in the <a href="%s" target="_blank">Now Playing API response</a> are avaliable for use. Any empty fields are ignored.'), $router->named('api:nowplaying:index')),
            'description_class' => 'col-sm-12',

            'elements' => [

                'content' => [
                    'text',
                    [
                        'label' => __('Main Message Content'),
                        'belongsTo' => 'config',
                        'default' => sprintf(__('Now playing on %s:'), '{{ station.name }}'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'title' => [
                    'text',
                    [
                        'label' => __('Title'),
                        'belongsTo' => 'config',
                        'default' => '{{ now_playing.song.title }}',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'description' => [
                    'text',
                    [
                        'label' => __('Description'),
                        'belongsTo' => 'config',
                        'default' => '{{ now_playing.song.artist }}',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'url' => [
                    'text',
                    [
                        'label' => __('URL'),
                        'belongsTo' => 'config',
                        'default' => '{{ station.listen_url }}',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'author' => [
                    'text',
                    [
                        'label' => __('Author Name'),
                        'belongsTo' => 'config',
                        'default' => '{{ live.streamer_name }}',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'thumbnail' => [
                    'text',
                    [
                        'label' => __('Thumbnail Image URL'),
                        'belongsTo' => 'config',
                        'default' => '{{ now_playing.song.art }}',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'footer' => [
                    'text',
                    [
                        'label' => __('Footer Text'),
                        'belongsTo' => 'config',
                        'default' => sprintf(__('Powered by %s'), $app_settings['name']),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
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
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],

            ]
        ]
    ],
];
