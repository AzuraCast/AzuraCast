<?php
use App\Entity\StationMount;

$form_config = require __DIR__.'/generic.php';

$form_config['groups']['basic_info']['elements']['fallback_mount'] = [
    'text',
    [
        'label' => __('Fallback Mount'),
        'description' => __('If this mount point is not playing audio, listeners will automatically be redirected to this mount point. The default is /error.mp3, a repeating error message.'),
        'default' => '/error.mp3',
        'form_group_class' => 'col-md-6',
    ]
];

$form_config['groups']['autodj']['elements']['autodj_format'][1]['choices'] = [
    StationMount::FORMAT_MP3 => 'MP3',
    StationMount::FORMAT_OGG => 'OGG Vorbis',
    StationMount::FORMAT_OPUS => 'OGG Opus',
    StationMount::FORMAT_AAC => 'AAC+ (MPEG4 HE-AAC v2)',
];
$form_config['groups']['autodj']['elements']['autodj_format'][1]['default'] = StationMount::FORMAT_MP3;

$form_config['groups']['advanced_items']['elements']['frontend_config'] = [
    'textarea',
    [
        'label' => __('Custom Frontend Configuration'),
        'label_class' => 'advanced',
        'description' => __('You can include any special mount point settings here, in either JSON { key: \'value\' } format or XML &lt;key&gt;value&lt;/key&gt;'),
        'form_group_class' => 'col-sm-12',
    ]
];

return $form_config;
