<?php

return array(
    'method'        => 'post',
    'elements' => array(

        'is_enabled' => array('radio', array(
            'label' => 'Enable Automated Assignment',
            'description' => 'Allow the system to periodically automatically assign songs to playlists based on their performance. This process will run in the background, and will only run if this option is set to "Enabled" and at least one playlist is set to "Include in Automated Assignment".',
            'default' => '0',
            'options' => array(
                0 => 'Disabled',
                1 => 'Enabled',
            ),
        )),

        'threshold_days' => array('radio', array(
            'label' => 'Days Between Automated Assignments',
            'description' => 'Based on this setting, the system will automatically reassign songs every (this) days using data from the previous (this) days.',
            'class' => 'inline',
            'default' => \App\Radio\Automation::DEFAULT_THRESHOLD_DAYS,
            'options' => array(
                7 => '7 days',
                14 => '14 days',
                30 => '30 days',
                60 => '60 days',
            ),
        )),

        'submit'        => array('submit', array(
            'type'  => 'submit',
            'label' => 'Save Changes',
            'helper' => 'formButton',
            'class' => 'btn btn-lg btn-primary',
        )),

    ),
);