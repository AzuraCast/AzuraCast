<?php

return [
    'method' => 'post',
    'elements' => [

        'is_enabled' => [
            'radio',
            [
                'label' => _('Enable Automated Assignment'),
                'description' => _('Allow the system to periodically automatically assign songs to playlists based on their performance. This process will run in the background, and will only run if this option is set to "Enabled" and at least one playlist is set to "Include in Automated Assignment".'),
                'default' => '0',
                'options' => [
                    0 => 'Disabled',
                    1 => 'Enabled',
                ],
            ]
        ],

        'threshold_days' => [
            'radio',
            [
                'label' => _('Days Between Automated Assignments'),
                'description' => _('Based on this setting, the system will automatically reassign songs every (this) days using data from the previous (this) days.'),
                'class' => 'inline',
                'default' => \AzuraCast\Sync\RadioAutomation::DEFAULT_THRESHOLD_DAYS,
                'options' => [
                    7 => '7 days',
                    14 => '14 days',
                    30 => '30 days',
                    60 => '60 days',
                ],
            ]
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => _('Save Changes'),
                'class' => 'btn btn-lg btn-primary',
            ]
        ],

    ],
];