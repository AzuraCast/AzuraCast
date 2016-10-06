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
                'legend' => _('System Settings'),
                'elements' => [

                    'base_url' => ['text', [
                        'label' => _('Site Base URL'),
                        'description' => _('The base URL where this service is located. For local testing, use "localhost". Otherwise, use either the external IP address or fully-qualified domain name pointing to the server.'),
                        'default' => $base_url_default,
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