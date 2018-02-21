<?php
return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'elements' => [

        'partner_id' => [
            'text',
            [
                'label' => _('TuneIn Partner ID'),
                'belongsTo' => 'config',
                'required' => true,
            ]
        ],

        'partner_key' => [
            'text',
            [
                'label' => _('TuneIn Partner Key'),
                'belongsTo' => 'config',
                'required' => true,
            ]
        ],

        'station_id' => [
            'text',
            [
                'label' => _('TuneIn Station ID'),
                'description' => _('The station ID will be a numeric string that starts with the letter S.'),
                'belongsTo' => 'config',
                'required' => true,
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => _('Save Changes'),
                'class' => 'ui-button btn-lg btn-primary',
            ]
        ],
    ],
];