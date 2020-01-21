<?php

use App\Entity\StationMount;

$form_config = require __DIR__ . '/generic.php';

$form_config['groups']['basic_info']['elements']['name'][1]['validator'] = function ($text) {
    $forbidden_paths = ['listen', 'admin', 'statistics', '7.html'];

    foreach ($forbidden_paths as $forbidden_path) {
        if (stripos($text, $forbidden_path) !== false) {
            return __('Stream path cannot include reserved keywords: %s', implode(', ', $forbidden_paths));
        }
    }

    return true;
};

$form_config['groups']['basic_info']['elements']['authhash'] = [
    'text',
    [
        'label' => __('YP Directory Authorization Hash'),
        'description' => __('If your stream is set to advertise to YP directories above, you must specify an authorization hash. You can manage authhashes <a href="%s" target="_blank">on the SHOUTcast web site</a>.',
            'https://rmo.shoutcast.com'),
        'default' => '',
        'form_group_class' => 'col-sm-12',
    ],
];

$form_config['groups']['autodj']['elements']['autodj_format'][1]['choices'] = [
    StationMount::FORMAT_MP3 => 'MP3',
    StationMount::FORMAT_AAC => 'AAC+ (MPEG4 HE-AAC v2)',
];

$form_config['groups']['autodj']['elements']['autodj_format'][1]['default'] = StationMount::FORMAT_MP3;

return $form_config;
