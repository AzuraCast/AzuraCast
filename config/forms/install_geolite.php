<?php
return [
    'method' => 'post',
    'groups' => [
        [
            'elements' => [
                'details' => [
                    'markup',
                    [
                        'label' => __('Instructions'),
                        'markup' =>
                            '<p>' . __('You can upload the MaxMind GeoLite database in order to provide geolocation of the IP addresses of your listeners. This will allow you to view the listeners on each station\'s "Listeners" report. To download the GeoLite database:') . '</p>' .
                            '<ul>' .
                            '<li>' . __('Create an account on <a href="%s" target="_blank">the MaxMind developer site</a>.',
                                'https://www.maxmind.com/en/geolite2/signup') . '</li>' .
                            '<li>' . __('Visit the "My License Key" page under the "Services" section.') . '</li>' .
                            '<li>' . __('Click "Generate new license key".') . '</li>' .
                            '<li>' . __('Paste the generated license key into the field on this page.') . '</li>'
                            . '</ul>',
                    ],
                ],

                'geoliteLicenseKey' => [
                    'text',
                    [
                        'label' => __('MaxMind License Key'),
                        'default' => '',
                    ],
                ],

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
