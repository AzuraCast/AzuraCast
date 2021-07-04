<?php
/** @var \App\Environment $environment */

$locale_select = \App\Locale::SUPPORTED_LOCALES;
$locale_select = ['default' => __('Use Browser Default')] + $locale_select;

return [
    'method' => 'post',
    'groups' => [

        'account_info' => [
            'use_grid' => true,
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('Name'),
                        'class' => 'half-width',
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'email' => [
                    'text',
                    [
                        'label' => __('E-mail Address'),
                        'class' => 'half-width',
                        'required' => true,
                        'autocomplete' => 'off',
                        'form_group_class' => 'col-md-6',
                    ],
                ],

            ],
        ],

        'reset_password' => [
            'use_grid' => true,
            'legend' => __('Reset Password'),
            'description' => __('Leave these fields blank to continue using your current password.'),
            'elements' => [

                'password' => [
                    'password',
                    [
                        'label' => __('Current Password'),
                        'autocomplete' => 'off',
                        'form_group_class' => 'col-md-4',
                    ],
                ],

                'new_password' => [
                    'password',
                    [
                        'label' => __('New Password'),
                        'autocomplete' => 'new-password',
                        'class' => 'strength',
                        'confirm' => 'new_password_confirm',
                        'form_group_class' => 'col-md-4',
                    ],
                ],

                'new_password_confirm' => [
                    'password',
                    [
                        'label' => __('Confirm New Password'),
                        'autocomplete' => 'new-password',
                        'form_group_class' => 'col-md-4',
                    ],
                ],

            ],
        ],

        'customization' => [
            'use_grid' => true,
            'legend' => __('Customization'),
            'elements' => [

                'locale' => [
                    'radio',
                    [
                        'label' => __('Language'),
                        'options' => $locale_select,
                        'default' => 'default',
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'theme' => [
                    'radio',
                    [
                        'label' => __('Site Theme'),
                        'choices' => [
                            App\Customization::THEME_BROWSER => __('Prefer System Default'),
                            App\Customization::THEME_LIGHT => __('Light'),
                            App\Customization::THEME_DARK => __('Dark'),
                        ],
                        'default' => App\Customization::DEFAULT_THEME,
                        'form_group_class' => 'col-md-6',
                    ],
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
                    ],
                ],
            ],
        ],

    ],
];
