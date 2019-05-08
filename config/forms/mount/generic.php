<?php
return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [

        'basic_info' => [
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('Mount Point URL'),
                        'description' => __('This name should always begin with a slash (/), and must be a valid URL, such as /autodj.mp3'),
                        'required' => true,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'display_name' => [
                    'text',
                    [
                        'label' => __('Display Name'),
                        'description' => __('The display name assigned to this mount point when viewing it on administrative or public pages. Leave blank to automatically generate one.'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'is_visible_on_public_pages' => [
                    'toggle',
                    [
                        'label' => __('Show on Public Pages'),
                        'description' => __('Enable to allow listeners to select this mount point on this station\'s public pages.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => true,
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'is_default' => [
                    'toggle',
                    [
                        'label' => __('Set as Default Mount Point'),
                        'description' => __('If this mount is the default, it will be played on the radio preview and the public radio page in this system.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'relay_url' => [
                    'text',
                    [
                        'label' => __('Relay Stream URL'),
                        'description' => __('Enter the full URL of another stream to relay its broadcast through this mount point.'),
                        'default' => '',
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'is_public' => [
                    'toggle',
                    [
                        'label' => __('Publish to "Yellow Pages" Directories'),
                        'description' => __('Enable to advertise this mount point on "Yellow Pages" public radio directories.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'enable_autodj' => [
                    'toggle',
                    [
                        'label' => __('Enable AutoDJ'),
                        'description' => __('If enabled, the AutoDJ will automatically play music to this mount point.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => true,
                        'form_group_class' => 'col-sm-12 mt-1',
                    ]
                ],
            ],
        ],

        'autodj' => [
            'legend' => __('Configure AutoDJ Broadcasting'),
            'legend_class' => 'd-none',
            'class' => 'fieldset_autodj',
            'elements' => [

                'autodj_format' => [
                    'radio',
                    [
                        'label' => __('AutoDJ Format'),
                        'choices' => [],
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'autodj_bitrate' => [
                    'radio',
                    [
                        'label' => __('AutoDJ Bitrate (kbps)'),
                        'choices' => [
                            32 => '32',
                            48 => '48',
                            64 => '64',
                            96 => '96',
                            128 => '128',
                            192 => '192',
                            256 => '256',
                            320 => '320',
                        ],
                        'default' => 128,
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],


            ],
        ],

        'advanced_items' => [
            'legend' => __('Advanced Configuration'),
            'legend_class' => 'd-none',
            'elements' => [

                'custom_listen_url' => [
                    'text',
                    [
                        'label' => __('Custom Stream URL'),
                        'label_class' => 'advanced mb-2',
                        'description' => __('You can set a custom URL for this stream that AzuraCast will use when referring to it. Leave empty to use the default value.'),
                        'form_group_class' => 'col-sm-12 mt-1',
                    ]
                ],

            ],
        ],

        'grp_submit' => [
            'elements' => [

                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Save Changes'),
                        'class' => 'ui-button btn-lg btn-primary',
                        'form_group_class' => 'col-sm-12 mt-1',
                    ]
                ],

            ],
        ],
    ],
];
