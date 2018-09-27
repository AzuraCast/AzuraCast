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
            ]
        ],

        'binary' => [
            'file',
            [
                'label' => __('Select SHOUTcast 64-bit .tar.gz File'),
                'required' => true,
                'type' => [
                    'application/x-gzip',
                    'application/tar+gzip',
                    'application/octet-stream',
                ],
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Upload'),
                'class' => 'ui-button btn-lg btn-primary',
            ]
        ],
    ],
];
