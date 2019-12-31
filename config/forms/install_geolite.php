<?php
return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [
        [
            'use_grid' => true,
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
                            '<li>' . __('Visit the <a href="%s" target="_blank">direct downloads page</a>.',
                                'https://www.maxmind.com/en/download_files?direct=1') . '</li>' .
                            '<li>' . __('Download the <code>%s</code> file (in GZIP) format.',
                                'GeoLite2-City') . '</li>' .
                            '<li>' . __('Select the downloaded file below to upload it.') . '</li>'
                            . '</ul>' .
                            '<p>' . __('You can repeat this process any time you need to update the GeoLite database.') . '</p>',
                        'form_group_class' => 'col-sm-12',
                    ],
                ],

                'current_version' => [
                    'markup',
                    [
                        'label' => __('Current Installed Version'),
                        'markup' => '<p class="text-danger">' . __('GeoLite is not currently installed on this installation.') . '</p>',
                        'form_group_class' => 'col-sm-12',
                    ],
                ],

                'binary' => [
                    'file',
                    [
                        'label' => __('Select GeoLite2-City .tar.gz File'),
                        'required' => true,
                        'type' => 'archive',
                        'max_size' => 50 * 1024 * 1024,
                        'form_group_class' => 'col-md-6',
                        'button_text' => __('Select File'),
                        'button_icon' => 'cloud_upload',
                    ],
                ],

                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Upload'),
                        'class' => 'ui-button btn-lg btn-primary',
                        'form_group_class' => 'col-sm-12',
                    ],
                ],
            ],
        ],
    ],
];
