<?php
/** @var array $app_settings */
/** @var array $triggers */
/** @var \App\Http\Router $router */

return [
    'method' => 'post',

    'elements' => [

        'name' => [
            'text',
            [
                'label' => __('%s Name', __('Web Hook')),
                'description' => __('Choose a name for this webhook that will help you distinguish it from others. This will only be shown on the administration page.'),
                'required' => true,
                'label_class' => 'mb-2',
                'form_group_class' => 'col-md-6 mt-1',
            ]
        ],

        'station_id' => [
            'text',
            [
                'label' => __('TuneIn Station ID'),
                'description' => __('The station ID will be a numeric string that starts with the letter S.'),
                'belongsTo' => 'config',
                'required' => true,
                'label_class' => 'mb-2',
                'form_group_class' => 'col-md-6 mt-1',
            ]
        ],

        'partner_id' => [
            'text',
            [
                'label' => __('TuneIn Partner ID'),
                'belongsTo' => 'config',
                'required' => true,
                'label_class' => 'mb-2',
                'form_group_class' => 'col-md-6 mt-1',
            ]
        ],

        'partner_key' => [
            'text',
            [
                'label' => __('TuneIn Partner Key'),
                'belongsTo' => 'config',
                'required' => true,
                'label_class' => 'mb-2',
                'form_group_class' => 'col-md-6 mt-1',
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Save Changes'),
                'class' => 'ui-button btn-lg btn-primary',
                'form_group_class' => 'col-sm-12 mt-4',
            ]
        ],
    ],
];
