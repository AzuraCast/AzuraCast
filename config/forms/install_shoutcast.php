<?php
return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'elements' => [

        'details' => [
            'markup',
            [
                'label' => __('Important Notes'),
                'markup' => __('<p>SHOUTcast 2 DNAS is not free software, and its restrictive license does not allow AzuraCast to distribute the SHOUTcast binary. In order to install SHOUTcast, you should download the Linux x64 binary from the <a href="%s" target="_blank">SHOUTcast Radio Manager</a> web site. Upload the <code>sc_serv2_linux_x64-latest.tar.gz</code> into the field below to automatically extract it into the proper directory.</p>', 'https://radiomanager.shoutcast.com/register/serverSoftwareFreemium'),
                'label_class' => 'mb-2',
                'form_group_class' => 'col-sm-12 mt-3',
            ]
        ],

        'current_version' => [
            'markup',
            [
                'label' => __('Current Installed Version'),
                'markup' => '<p class="text-danger">'.__('SHOUTcast is not currently installed on this installation.').'</p>',
                'label_class' => 'mb-2',
                'form_group_class' => 'col-sm-12 mt-3',
            ]
        ],

        'binary' => [
            'file',
            [
                'label' => __('Select SHOUTcast 64-bit .tar.gz File'),
                'required' => true,
                'type' => 'archive',
                'label_class' => 'mb-2',
                'form_group_class' => 'col-md-6 mt-3',
                'button_text' => __('Select File'),
                'button_icon' => 'cloud_upload',
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Upload'),
                'class' => 'ui-button btn-lg btn-primary',
                'form_group_class' => 'col-sm-12 mt-3',
            ]
        ],
    ],
];
