<?php
/**
 * Profile Form
 */

$settings = $di['app_settings'];

$locale_select = $settings['locale']['supported'];
$locale_select = ['default' => _('Use Browser Default')] + $locale_select;

return [
    'method' => 'post',
    'groups' => [

        'account_info' => [
            'legend' => _('Account Information'),
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => _('Name'),
                        'class' => 'half-width',
                    ]
                ],

                'email' => [
                    'text',
                    [
                        'label' => _('E-mail Address'),
                        'class' => 'half-width',
                        'required' => true,
                        'autocomplete' => 'off',
                    ]
                ],

                'auth_password' => [
                    'password',
                    [
                        'label' => _('Reset Password'),
                        'description' => _('To change your password, enter the new password in the field below.'),
                        'autocomplete' => 'off',
                    ]
                ],

            ],
        ],

        'customization' => [
            'legend' => _('Customization'),
            'elements' => [

                'timezone' => [
                    'select',
                    [
                        'label' => _('Time Zone'),
                        'description' => _('All times displayed on the site will be based on this time zone.') . '<br>' . sprintf(_('Current server time is <b>%s</b>.'),
                                date('g:ia')),
                        'options' => \App\Timezone::fetchSelect(),
                        'default' => 'UTC',
                    ]
                ],

                'locale' => [
                    'radio',
                    [
                        'label' => _('Language'),
                        'options' => $locale_select,
                        'default' => 'default',
                    ]
                ],

                'theme' => [
                    'radio',
                    [
                        'label' => _('Site Theme'),
                        'options' => $settings['themes']['available'],
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
                        'label' => _('Save Changes'),
                        'class' => 'btn btn-lg btn-primary',
                    ]
                ],
            ],
        ],

    ],
];