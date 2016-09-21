<?php
$hour_select = [];
for($hr = 0; $hr <= 23; $hr++)
{
    foreach([0, 15, 30, 45] as $min)
    {
        $time_num = $hr*100 + $min;
        $hour_select[$time_num] = \Entity\StationPlaylist::formatTimeCode($time_num);
    }
}

return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [

        'basic_info' => [
            'elements' => [

                'name' => ['text', [
                    'label' => 'Playlist Name',
                    'required' => true,
                ]],

                'is_enabled' => ['radio', [
                    'label' => 'Enable Playlist',
                    'required' => true,
                    'description' => 'If set to "No", the playlist will not be included in radio playback, but can still be managed.',
                    'options' => [
                        1 => 'Yes',
                        0 => 'No',
                    ],
                    'default' => 1,
                ]],

                'type' => ['radio', [
                    'label' => 'Playlist Type',
                    'options' => [
                        'default' => '<b>Standard:</b> Plays all day, shuffles with other standard playlists based on weight.',
                        'scheduled' => '<b>Scheduled:</b> Play during a scheduled time range. Useful for mood-based time playlists.',
                        'once_per_x_songs' => '<b>Once per x Songs:</b> Play exactly once every <i>x</i> songs. Useful for station ID/jingles.',
                        'once_per_x_minutes' => '<b>Once Per x Minutes:</b> Play exactly once every <i>x</i> minutes. Useful for station ID/jingles.',
                        'once_per_day' => '<b>Daily</b>: Play once per day at the specified time. Useful for timely reminders.',
                    ],
                    'default' => 'default',
                    'required' => true,
                ]],

            ],
        ],

        'type_default' => [
            'legend' => 'Standard Playlist',
            'class' => 'type_fieldset',
            'elements' => [

                'weight' => ['radio', [
                    'label' => 'Playlist Weight',
                    'description' => 'How often the playlist\'s songs will be played. 1 is the most infrequent, 5 is the most frequent.',
                    'default' => 3,
                    'required' => true,
                    'class' => 'inline',
                    'options' => [
                        1 => '1 - Lowest',
                        2 => '2',
                        3 => '3 - Default',
                        4 => '4',
                        5 => '5 - Highest',
                    ],
                ]],

                'include_in_automation' => ['radio', [
                    'label' => 'Include in Automated Assignment',
                    'description' => 'If auto-assignment is enabled, use this playlist as one of the targets for songs to be redistributed into. This will overwrite the existing contents of this playlist.',
                    'required' => true,
                    'default' => '0',
                    'options' => [
                        0 => 'No',
                        1 => 'Yes',
                    ],
                ]],

            ],
        ],

        'type_scheduled' => [
            'legend' => 'Scheduled Playlist',
            'class' => 'type_fieldset',
            'elements' => [

                'schedule_start_time' => ['select', [
                    'label' => 'Start Time',
                    'description' => 'Current server time is <b>'.date('g:ia').'.</b>',
                    'options' => $hour_select,
                ]],

                'schedule_end_time' => ['select', [
                    'label' => 'End Time',
                    'description' => 'If the end time is before the start time, the playlist will play overnight until this time on the next day.',
                    'options' => $hour_select,
                ]],

            ],
        ],

        'type_once_per_x_songs' => [
            'legend' => 'Once per x Songs Playlist',
            'class' => 'type_fieldset',
            'elements' => [

                'play_per_songs' => ['radio', [
                    'label' => 'Number of Songs Between Plays',
                    'description' => 'This playlist will play every $x songs, where $x is specified below.',
                    'options' => \App\Utilities::pairs([
                        5,
                        10,
                        15,
                        20,
                        25,
                        50,
                        100
                    ]),
                ]],

            ],
        ],

        'type_once_per_x_minutes' => [
            'legend' => 'Once per x Minutes Playlist',
            'class' => 'type_fieldset',
            'elements' => [

                'play_per_minutes' => ['radio', [
                    'label' => 'Number of Minutes Between Plays',
                    'description' => 'This playlist will play every $x minutes, where $x is specified below.',
                    'options' => \App\Utilities::pairs([
                        5,
                        10,
                        15,
                        30,
                        45,
                        60,
                        120,
                        240,
                    ]),
                ]],

            ],
        ],

        'type_once_per_day' => [
            'legend' => 'Daily Playlist',
            'class' => 'type_fieldset',
            'elements' => [

                'play_once_time' => ['select', [
                    'label' => 'Scheduled Play Time',
                    'description' => 'Current server time is <b>'.date('g:ia').'.</b>',
                    'options' => $hour_select,
                ]],

            ],
        ],

        'grp_submit' => [
            'elements' => [

                'submit' => ['submit', [
                    'type' => 'submit',
                    'label' => 'Save Changes',
                    'helper' => 'formButton',
                    'class' => 'ui-button btn-lg btn-primary',
                ]],

            ],
        ],
    ],
];