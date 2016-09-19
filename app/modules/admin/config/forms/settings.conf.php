<?php
/**
 * Settings form.
 */

$base_url_parts = parse_url($_SERVER['HTTP_HOST']);
$base_url_default = $base_url_parts['host'];

return [
    /**
     * Form Configuration
     */
    'form' => [
        'method' => 'post',

        'groups' => [

            'system' => [
                'legend' => 'System Settings',
                'elements' => [

                    'base_url' => ['text', [
                        'label' => 'Site Base URL',
                        'description' => 'The base URL where this service is located. For local testing, use "localhost". Otherwise, use either the external IP address or fully-qualified domain name pointing to the server.',
                        'default' => $base_url_default,
                    ]],

                    'timezone' => ['select', [
                        'label' => 'Server Timezone',
                        'description' => 'All times displayed on the site will be based on this time zone.<br><b>Current server time is ' . date('F j, Y g:ia') . '.</b>',
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
                        'label' => 'Save Changes',
                        'helper' => 'formButton',
                        'class' => 'btn btn-lg btn-primary',
                    ]],
                ],
            ],

        ],
    ],
];