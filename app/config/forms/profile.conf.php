<?php
$locale_select = $settings['locale']['supported'];
$locale_select = ['default' => __('Use Browser Default')] + $locale_select;

return [
    'method' => 'post',
    'groups' => [

        'account_info' => [
            'legend' => __('Account Information'),
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('Name'),
                        'class' => 'half-width',
                    ]
                ],

                'email' => [
                    'text',
                    [
                        'label' => __('E-mail Address'),
                        'class' => 'half-width',
                        'required' => true,
                        'autocomplete' => 'off',
                    ]
                ],

            ],
        ],

        'reset_password' => [
            'legend' => __('Reset Password'),
            'description' => __('Leave these fields blank to continue using your current password.'),
            'elements' => [

                'password' => [
                    'password',
                    [
                        'label' => __('Current Password'),
                        'autocomplete' => 'off',
                        'filter' => function($val) {
                            return '';
                        },
                        'validator' => function($val, $element) {
                            return false; // Safe default.
                        },
                    ]
                ],

                'new_password' => [
                    'password',
                    [
                        'label' => __('New Password'),
                        'autocomplete' => 'off',
                        'class' => 'strength',
                        'confirm' => 'new_password_confirm',
                    ]
                ],

                'new_password_confirm' => [
                    'password',
                    [
                        'label' => __('Confirm New Password'),
                        'autocomplete' => 'off',
                    ]
                ],

            ],
        ],

        'customization' => [
            'legend' => __('Customization'),
            'elements' => [

                'timezone' => [
                    'select',
                    [
                        'label' => __('Time Zone'),
                        'description' => __('All times displayed on the site will be based on this time zone.') . '<br>' . sprintf(__('Current server time is <b>%s</b>.'),
                                date('g:ia')),
                        'options' => \App\Timezone::fetchSelect(),
                        'default' => 'UTC',
                    ]
                ],

                'locale' => [
                    'radio',
                    [
                        'label' => __('Language'),
                        'options' => $locale_select,
                        'default' => 'default',
                    ]
                ],

                'theme' => [
                    'radio',
                    [
                        'label' => __('Site Theme'),
                        'choices' => [
                            'light' => __('Light').' ('.__('Default').')',
                            'dark' => __('Dark'),
                        ],
                        'default' => $settings['themes']['default'],
                    ]
                ],

            ],
        ],

        'submit' => [
            'elements' => [
                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Save Changes'),
                        'class' => 'btn btn-lg btn-primary',
                    ]
                ],
            ],
        ],

    ],
];