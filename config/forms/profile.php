<?php
$locale_select = $settings['locale']['supported'];
$locale_select = ['default' => __('Use Browser Default')] + $locale_select;

return [
    'method' => 'post',
    'groups' => [

        'account_info' => [
            'legend' => __('Account Information'),
            'legend_class' => 'd-none',
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('Name'),
                        'class' => 'half-width',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'email' => [
                    'text',
                    [
                        'label' => __('E-mail Address'),
                        'class' => 'half-width',
                        'required' => true,
                        'autocomplete' => 'off',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

            ],
        ],

        'reset_password' => [
            'legend' => __('Reset Password'),
            'legend_class' => 'col-sm-12',
            'description' => __('Leave these fields blank to continue using your current password.'),
            'description_class' => 'col-sm-12',
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
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-4',
                    ]
                ],

                'new_password' => [
                    'password',
                    [
                        'label' => __('New Password'),
                        'autocomplete' => 'off',
                        'class' => 'strength',
                        'confirm' => 'new_password_confirm',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-4',
                    ]
                ],

                'new_password_confirm' => [
                    'password',
                    [
                        'label' => __('Confirm New Password'),
                        'autocomplete' => 'off',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-4',
                    ]
                ],

            ],
        ],

        'customization' => [
            'legend' => __('Customization'),
            'legend_class' => 'col-sm-12',
            'elements' => [

                'timezone' => [
                    'select',
                    [
                        'label' => __('Time Zone'),
                        'description' => __('All times displayed on the site will be based on this time zone.') . '<br>' . sprintf(__('Current server time is <b>%s</b>.'),
                                date('g:ia')),
                        'options' => \Azura\Timezone::fetchSelect(),
                        'default' => \App\Customization::DEFAULT_TIMEZONE,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],

                'locale' => [
                    'radio',
                    [
                        'label' => __('Language'),
                        'options' => $locale_select,
                        'default' => 'default',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-3',
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
                        'default' => \App\Customization::DEFAULT_THEME,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-3',
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
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],
            ],
        ],

    ],
];
