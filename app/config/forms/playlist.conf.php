<?php
use \Entity\StationPlaylist;

/** @var \AzuraCast\Customization $customization */

$local_time_offset = \App\Timezone::getOffsetMinutes(null);
$local_time_hours = floor($local_time_offset / 60);
$local_time_mins = $local_time_offset % 60;

$hour_select_utc = [];
$local_to_utc = [];

for ($hr = 0; $hr <= 23; $hr++) {
    foreach ([0, 15, 30, 45] as $min) {
        $time_num = ($hr * 100) + $min;
        $local_time = $time_num + ($local_time_hours * 100) + $local_time_mins;

        if ($local_time > 2400) {
            $local_time = 2400-$local_time;
        } else if ($local_time < 0) {
            $local_time = 2400 + $local_time;
        }

        $local_to_utc[$local_time] = $time_num;

        $hour_timestamp = StationPlaylist::getTimestamp($time_num);

        $hour_select_utc[$time_num] = sprintf('%s (%s)',
            $customization->formatTime($hour_timestamp, false, true),
            $customization->formatTime($hour_timestamp, true, true)
        );
    }
}

// Sort select so 12:00AM appears at the top.
ksort($local_to_utc, \SORT_NUMERIC);

$hour_select = [];
foreach($local_to_utc as $local_time => $utc_time) {
    $hour_select[$utc_time] = $hour_select_utc[$utc_time];
}

$server_time = __('Your current local time is <b>%s</b> (%s UTC). You can customize your time zone from the "My Account" page.',
    $customization->formatTime(),
    $customization->formatTime(null, true)
);

return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [

        'basic_info' => [
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('Playlist Name'),
                        'required' => true,
                    ]
                ],

                'is_enabled' => [
                    'radio',
                    [
                        'label' => __('Enable Playlist'),
                        'required' => true,
                        'description' => __('If set to "No", the playlist will not be included in radio playback, but can still be managed.'),
                        'options' => [
                            1 => __('Yes'),
                            0 => __('No'),
                        ],
                        'default' => 1,
                    ]
                ],

                'import' => [
                    'file',
                    [
                        'label' => __('Import Existing Playlist'),
                        'description' => __('Select an existing playlist file to add its contents to this playlist. PLS and M3U are supported.'),
                        'required' => false,
                        'type' => [
                            'audio/x-scpls',
                            'application/vnd.apple.mpegurl',
                            'application/mpegurl',
                            'application/x-mpegurl',
                            'audio/mpegurl',
                            'audio/x-mpegurl',
                            'application/octet-stream',
                        ],
                    ]
                ],

                'weight' => [
                    'select',
                    [
                        'label' => __('Playlist Weight'),
                        'description' => __('Higher weight playlists are played more frequently compared to other lower-weight playlists.'),
                        'default' => 3,
                        'required' => true,
                        'options' => [
                            1 => '1 - '.__('Low'),
                            2 => '2',
                            3 => '3 - '.__('Default'),
                            4 => '4',
                            5 => '5 - '.__('High'),
                        ] + \App\Utilities::pairs(range(6, 25)),
                    ]
                ],

                'type' => [
                    'radio',
                    [
                        'label' => __('Playlist Type'),
                        'options' => [
                            'default' => '<b>' . __('Standard Playlist') . ':</b> ' . __('Plays all day, shuffles with other standard playlists based on weight.'),
                            'scheduled' => '<b>' . __('Scheduled Playlist') . ':</b> ' . __('Play during a scheduled time range. Useful for mood-based time playlists.'),
                            'once_per_x_songs' => '<b>' . __('Once per x Songs Playlist') . ':</b> ' . __('Play exactly once every <i>x</i> songs. Useful for station ID/jingles.'),
                            'once_per_x_minutes' => '<b>' . __('Once Per x Minutes Playlist') . ':</b> ' . __('Play exactly once every <i>x</i> minutes. Useful for station ID/jingles.'),
                            'once_per_day' => '<b>' . __('Daily Playlist') . '</b>: ' . __('Play once per day at the specified time. Useful for timely reminders.'),
                            'custom' => '<b>' . __('Custom Playlist') .'</b>: ' . __('Manually define how this playlist is used in Liquidsoap configuration. For advanced users only!'),
                        ],
                        'default' => 'default',
                        'required' => true,
                    ]
                ],

            ],
        ],

        'type_default' => [
            'legend' => __('Standard Playlist'),
            'class' => 'type_fieldset',
            'elements' => [

                'include_in_automation' => [
                    'radio',
                    [
                        'label' => __('Include in Automated Assignment'),
                        'description' => __('If auto-assignment is enabled, use this playlist as one of the targets for songs to be redistributed into. This will overwrite the existing contents of this playlist.'),
                        'required' => true,
                        'default' => '0',
                        'options' => [
                            0 => __('No'),
                            1 => __('Yes'),
                        ],
                    ]
                ],

            ],
        ],

        'type_scheduled' => [
            'legend' => __('Scheduled Playlist'),
            'class' => 'type_fieldset',
            'elements' => [

                'schedule_start_time' => [
                    'select',
                    [
                        'label' => __('Start Time'),
                        'description' => $server_time,
                        'options' => $hour_select,
                    ]
                ],

                'schedule_end_time' => [
                    'select',
                    [
                        'label' => __('End Time'),
                        'description' => __('If the end time is before the start time, the playlist will play overnight until this time on the next day.'),
                        'options' => $hour_select,
                    ]
                ],

                'schedule_days' => [
                    'checkbox',
                    [
                        'label' => __('Scheduled Play Days of Week'),
                        'description' => __('Leave blank to play on every day of the week.'),
                        'options' => [
                            1 => __('Monday'),
                            2 => __('Tuesday'),
                            3 => __('Wednesday'),
                            4 => __('Thursday'),
                            5 => __('Friday'),
                            6 => __('Saturday'),
                            7 => __('Sunday'),
                        ]
                    ]
                ],

            ],
        ],

        'type_once_per_x_songs' => [
            'legend' => __('Once per x Songs Playlist'),
            'class' => 'type_fieldset',
            'elements' => [

                'play_per_songs' => [
                    'select',
                    [
                        'label' => __('Number of Songs Between Plays'),
                        'description' => __('This playlist will play every $x songs, where $x is specified below.'),
                        'options' => \App\Utilities::pairs(range(1, 100)),
                    ]
                ],

            ],
        ],

        'type_once_per_x_minutes' => [
            'legend' => __('Once per x Minutes Playlist'),
            'class' => 'type_fieldset',
            'elements' => [

                'play_per_minutes' => [
                    'select',
                    [
                        'label' => __('Number of Minutes Between Plays'),
                        'description' => __('This playlist will play every $x minutes, where $x is specified below.'),
                        'options' => \App\Utilities::pairs(range(1, 240)),
                    ]
                ],

            ],
        ],

        'type_once_per_day' => [
            'legend' => __('Daily Playlist'),
            'class' => 'type_fieldset',
            'elements' => [

                'play_once_time' => [
                    'select',
                    [
                        'label' => __('Scheduled Play Time'),
                        'description' => $server_time,
                        'options' => $hour_select,
                    ]
                ],

                'play_once_days' => [
                    'checkbox',
                    [
                        'label' => __('Scheduled Play Days of Week'),
                        'description' => __('Leave blank to play on every day of the week.'),
                        'options' => [
                            1 => __('Monday'),
                            2 => __('Tuesday'),
                            3 => __('Wednesday'),
                            4 => __('Thursday'),
                            5 => __('Friday'),
                            6 => __('Saturday'),
                            7 => __('Sunday'),
                        ]
                    ]
                ],

            ],
        ],

        'grp_submit' => [
            'elements' => [

                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Save Changes'),
                        'class' => 'ui-button btn-lg btn-primary',
                    ]
                ],

            ],
        ],
    ],
];