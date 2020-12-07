<?php

return [
    'method' => 'post',
    'elements' => [

        'is_enabled' => [
            'radio',
            [
                'label' => __('Enable Automated Assignment'),
                'description' => __('Allow the system to periodically automatically assign songs to playlists based on their performance. This process will run in the background, and will only run if this option is set to "Enabled" and at least one playlist is set to "Include in Automated Assignment".'),
                'default' => '0',
                'choices' => [
                    0 => __('Disabled'),
                    1 => __('Enabled'),
                ],
            ],
        ],

        'threshold_days' => [
            'radio',
            [
                'label' => __('Days Between Automated Assignments'),
                'description' => __('Based on this setting, the system will automatically reassign songs every (this) days using data from the previous (this) days.'),
                'class' => 'inline',
                'default' => App\Sync\Task\RunAutomatedAssignmentTask::DEFAULT_THRESHOLD_DAYS,
                'choices' => [
                    7 => sprintf(__('%d days'), 7),
                    14 => sprintf(__('%d days'), 14),
                    30 => sprintf(__('%d days'), 30),
                    60 => sprintf(__('%d days'), 60),
                ],
            ],
        ],

        'submit' => [
            'submit',
            [
                'type' => 'submit',
                'label' => __('Save Changes'),
                'class' => 'btn btn-lg btn-primary',
            ],
        ],

    ],
];
