<?php
/**
 * @var array $triggers
 * @var App\Environment $environment
 * @var App\Http\Router $router
 */

return [
    'method' => 'post',

    'groups' => [

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

                'to' => [
                    'text',
                    [
                        'label' => __('Message Recipient(s)'),
                        'belongsTo' => 'config',
                        'required' => true,
                        'description' => __('E-mail addresses can be separated by commas.'),
                        'form_group_class' => 'col-sm-6',
                    ],
                ],

                'subject' => [
                    'text',
                    [
                        'label' => __('Message Subject'),
                        'belongsTo' => 'config',
                        'required' => true,
                        'description' => __(
                            'Variables are in the form of <code>{{ var.name }}</code>. All values in the <a href="%s" target="_blank">Now Playing API response</a> are avaliable for use. Any empty fields are ignored.',
                            $router->named('api:nowplaying:index')
                        ),
                        'form_group_class' => 'col-sm-6',
                    ],
                ],

                'message' => [
                    'textarea',
                    [
                        'label' => __('Message Body'),
                        'belongsTo' => 'config',
                        'required' => true,
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
