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

                'bot_token' => [
                    'text',
                    [
                        'label' => __('Bot Token'),
                        'description' => __(
                            'See the <a href="%s" target="_blank">Telegram Documentation</a> for more details.',
                            'https://core.telegram.org/bots#botfather'
                        ),
                        'belongsTo' => 'config',
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'chat_id' => [
                    'text',
                    [
                        'label' => __('Chat ID'),
                        'description' => __(
                            'Unique identifier for the target chat or username of the target channel (in the format @channelusername).'
                        ),
                        'belongsTo' => 'config',
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'api' => [
                    'text',
                    [
                        'label' => __('Custom API Base URL'),
                        'label_class' => 'advanced',
                        'description' => __(
                            'Leave blank to use the default Telegram API URL (recommended). Specify the full URL, like <code>https://api.pwrtelegram.xyz/</code>.'
                        ),
                        'belongsTo' => 'config',
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'triggers' => [
                    'multiCheckbox',
                    [
                        'label' => __('Web Hook Triggers'),
                        'options' => array_diff_key($triggers, ['listener_lost' => 1, 'listener_gained' => 1]),
                        'required' => true,
                        'form_group_class' => 'col-md-6',
                    ],
                ],

            ],
        ],

        'message' => [
            'use_grid' => true,
            'legend' => __('Customize Message'),
            'legend_class' => 'd-none',
            'description' => sprintf(
                __(
                    'Variables are in the form of <code>{{ var.name }}</code>. All values in the <a href="%s" target="_blank">Now Playing API response</a> are avaliable for use. Any empty fields are ignored.'
                ),
                $router->named('api:nowplaying:index')
            ),

            'elements' => [

                'text' => [
                    'textarea',
                    [
                        'label' => __('Main Message Content'),
                        'belongsTo' => 'config',
                        'default' => sprintf(
                            __('Now playing on %s: %s by %s! Tune in now.'),
                            '{{ station.name }}',
                            '{{ now_playing.song.title }}',
                            '{{ now_playing.song.artist }}'
                        ),
                        'required' => true,
                        'form_group_class' => 'col-sm-12',
                    ],
                ],

                'parse_mode' => [
                    'radio',
                    [
                        'label' => __('Message parsing mode'),
                        'description' => __(
                            'See the <a href="%s" target="_blank">Telegram Documentation</a> for more details.',
                            'https://core.telegram.org/bots/api#sendmessage'
                        ),
                        'default' => 'Markdown',
                        'options' => [
                            'Markdown' => 'Markdown',
                            'HTML' => 'HTML',
                        ],
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
