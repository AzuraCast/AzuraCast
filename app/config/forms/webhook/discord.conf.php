<?php
/** @var \App\Url $url */
/** @var array $app_settings */

return [
    'method' => 'post',

    'groups' => [

        'api_info' => [
            'legend' => _('Discord API Details'),
            'elements' => [

                'webhook_url' => [
                    'text',
                    [
                        'label' => _('Discord Webhook URL'),
                        'description' => _('This URL is provided within the Discord application.'),
                        'belongsTo' => 'config',
                        'required' => true,
                    ]
                ],

                'triggers' => [
                    'multiCheckbox',
                    [
                        'label' => _('Webhook Triggers'),
                        'options' => \AzuraCast\Webhook\Dispatcher::getTriggers(),
                        'required' => true,
                    ]
                ],

            ],
        ],

        'message' => [
            'legend' => _('Customize Discord Message'),
            'description' => sprintf(_('Variables are in the form of {{ var.name }}. All values in the <a href="%s" target="_blank">Now Playing API response</a> are avaliable for use. Any empty fields are ignored.'), $url->named('api:nowplaying:index')),

            'elements' => [

                'content' => [
                    'text',
                    [
                        'label' => _('Main Message Content'),
                        'belongsTo' => 'config',
                        'default' => 'Now playing on {{ station.name }}:',
                    ]
                ],

                'title' => [
                    'text',
                    [
                        'label' => _('Title'),
                        'belongsTo' => 'config',
                        'default' => '{{ now_playing.song.title }}',
                    ]
                ],

                'description' => [
                    'text',
                    [
                        'label' => _('Description'),
                        'belongsTo' => 'config',
                        'default' => '{{ now_playing.song.artist }}',
                    ]
                ],

                'url' => [
                    'text',
                    [
                        'label' => _('URL'),
                        'belongsTo' => 'config',
                        'default' => '{{ station.listen_url }}',
                    ]
                ],

                'author' => [
                    'text',
                    [
                        'label' => _('Author Name'),
                        'belongsTo' => 'config',
                        'default' => '{{ live.streamer_name }}',
                    ]
                ],

                'thumbnail' => [
                    'text',
                    [
                        'label' => _('Thumbnail Image URL'),
                        'belongsTo' => 'config',
                        'default' => '{{ now_playing.song.art }}',
                    ]
                ],

                'footer' => [
                    'text',
                    [
                        'label' => _('Footer Text'),
                        'belongsTo' => 'config',
                        'default' => sprintf(_('Powered by %s'), $app_settings['name']),
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
                        'label' => _('Save Changes'),
                        'class' => 'ui-button btn-lg btn-primary',
                    ]
                ],

            ]
        ]
    ],
];