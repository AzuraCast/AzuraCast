<?php
return [
    'method' => 'post',

    'elements' => [

        'partner_id' => [
            'text',
            [
                'label' => __('TuneIn Partner ID'),
                'belongsTo' => 'config',
                'required' => true,
            ]
        ],

        'partner_key' => [
            'text',
            [
                'label' => __('TuneIn Partner Key'),
                'belongsTo' => 'config',
                'required' => true,
            ]
        ],

        'station_id' => [
            'text',
            [
                'label' => __('TuneIn Station ID'),
                'description' => __('The station ID will be a numeric string that starts with the letter S.'),
                'belongsTo' => 'config',
                'required' => true,
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Save Changes'),
                'class' => 'ui-button btn-lg btn-primary',
            ]
        ],
    ],
];