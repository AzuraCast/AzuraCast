<?php
/** @var \App\Url $url */
/** @var array $app_settings */

return [
    'method' => 'post',

    'groups' => [

        'api_info' => [
            'legend' => __('Telegram API Details'),
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('%s Name', __('Web Hook')),
                        'description' => __('Choose a name for this webhook that will help you distinguish it from others. This will only be shown on the administration page.'),
                        'required' => true,
                    ]
                ],

                'bot_token' => [
                    'text',
                    [
                        'label' => __('Bot Token'),
                        'description' => __('See the <a href="%s" target="_blank">Telegram Documentation</a> for more details.', 'https://core.telegram.org/bots#botfather'),
                        'belongsTo' => 'config',
                        'required' => true,
                    ]
                ],

                'chat_id' => [
                    'text',
                    [
                        'label' => __('Chat ID'),
                        'description' => __('Unique identifier for the target chat or username of the target channel (in the format @channelusername).'),
                        'belongsTo' => 'config',
                        'required' => true,
                    ]
                ],

                'api' => [
                    'text',
                    [
                        'label' => __('Advanced: Custom API Base URL'),
                        'description' => __('Leave blank to use the default Telegram API URL (recommended). Specify the full URL, like <code>https://api.pwrtelegram.xyz/</code>.'),
                        'belongsTo' => 'config',
                    ]
                ],

                'triggers' => [
                    'multiCheckbox',
                    [
                        'label' => __('Web Hook Triggers'),
                        'options' => array_diff_key($triggers, ['listener_lost' => 1, 'listener_gained' => 1]),
                        'required' => true,
                    ]
                ],

            ],
        ],

        'message' => [
            'legend' => __('Customize Message'),
            'description' => sprintf(__('Variables are in the form of <code>{{ var.name }}</code>. All values in the <a href="%s" target="_blank">Now Playing API response</a> are avaliable for use. Any empty fields are ignored.'), $url->named('api:nowplaying:index')),

            'elements' => [

                'text' => [
                    'textarea',
                    [
                        'label' => __('Main Message Content'),
                        'belongsTo' => 'config',
                        'default' => sprintf(__('Now playing on %s: %s by %s! Tune in now.'), '{{ station.name }}', '{{ now_playing.song.title }}', '{{ now_playing.song.artist }}'),
                        'required' => true,
                    ]
                ],

                'parse_mode' => [
                    'radio',
                    [
                        'label' => __('Message parsing mode'),
                        'description' => __('See the <a href="%s" target="_blank">Telegram Documentation</a> for more details.', 'https://core.telegram.org/bots/api#sendmessage'),
                        'default' => 'Markdown',
                        'options' => [
                            'Markdown' => 'Markdown',
                            'HTML' => 'HTML',
                        ],
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
