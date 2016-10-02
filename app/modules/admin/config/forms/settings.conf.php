<?php
/**
 * Settings form.
 */

$base_url_parts = parse_url($_SERVER['HTTP_HOST']);
$base_url_default = $base_url_parts['host'];

/** @var \App\Config $config */
$config = $di['config'];

return [
    /**
     * Form Configuration
     */
    'form' => [
        'method' => 'post',

        'groups' => [

            'system' => [
                'legend' => _('System Settings'),
                'elements' => [

                    'base_url' => ['text', [
                        'label' => _('Site Base URL'),
                        'description' => _('The base URL where this service is located. For local testing, use "localhost". Otherwise, use either the external IP address or fully-qualified domain name pointing to the server.'),
                        'default' => $base_url_default,
                    ]],

                    'locale' => ['radio', [
                        'label' => _('Default Language'),
                        'options' => $config->application->locale->supported->toArray(),
                        'default' => $config->application->locale->default,
                    ]],

                    'timezone' => ['select', [
                        'label' => _('Server Time Zone'),
                        'description' => _('All times displayed on the site will be based on this time zone.').'<br>'.sprintf(_('Current server time is <b>%s</b>.'), date('g:ia')),
                        'options' => \App\Timezone::fetchSelect(),
                        'default' => date_default_timezone_get(),
                    ]],

                ],
            ],

            'submit' => [
                'legend' => '',
                'elements' => [
                    'submit' => ['submit', [
                        'type' => 'submit',
                        'label' => _('Save Changes'),
                        'helper' => 'formButton',
                        'class' => 'btn btn-lg btn-primary',
                    ]],
                ],
            ],

        ],
    ],
];