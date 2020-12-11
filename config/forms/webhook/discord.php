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

                'webhook_url' => [
                    'url',
                    [
                        'label' => __('Discord Web Hook URL'),
                        'description' => __('This URL is provided within the Discord application.'),
                        'belongsTo' => 'config',
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'triggers' => [
                    'multiCheckbox',
                    [
                        'label' => __('Web Hook Triggers'),
                        'options' => array_diff_key($triggers, ['listener_lost' => 1, 'listener_gained' => 1]),
                        'required' => true,
                        'form_group_class' => 'col-sm-12',
                    ],
                ],

            ],
        ],

        'message' => [
            'use_grid' => true,
            'legend' => __('Customize Message'),
            'legend_class' => 'd-none',
            'description' => __(
                'Variables are in the form of <code>{{ var.name }}</code>. All values in the <a href="%s" target="_blank">Now Playing API response</a> are avaliable for use. Any empty fields are ignored.',
                $router->named('api:nowplaying:index')
            ),

            'elements' => [

                'content' => [
                    'text',
                    [
                        'label' => __('Main Message Content'),
                        'belongsTo' => 'config',
                        'default' => sprintf(__('Now playing on %s:'), '{{ station.name }}'),
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'title' => [
                    'text',
                    [
                        'label' => __('Title'),
                        'belongsTo' => 'config',
                        'default' => '{{ now_playing.song.title }}',
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'description' => [
                    'text',
                    [
                        'label' => __('Description'),
                        'belongsTo' => 'config',
                        'default' => '{{ now_playing.song.artist }}',
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'url' => [
                    'text',
                    [
                        'label' => __('URL'),
                        'belongsTo' => 'config',
                        'default' => '{{ station.listen_url }}',
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'author' => [
                    'text',
                    [
                        'label' => __('Author Name'),
                        'belongsTo' => 'config',
                        'default' => '{{ live.streamer_name }}',
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'thumbnail' => [
                    'text',
                    [
                        'label' => __('Thumbnail Image URL'),
                        'belongsTo' => 'config',
                        'default' => '{{ now_playing.song.art }}',
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'footer' => [
                    'text',
                    [
                        'label' => __('Footer Text'),
                        'belongsTo' => 'config',
                        'default' => sprintf(__('Powered by %s'), $environment->getAppName()),
                        'form_group_class' => 'col-md-6',
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
