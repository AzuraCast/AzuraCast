<?php
use \Entity\StationPlaylist;

/** @var \AzuraCast\Customization $customization */
$customization = $di[\AzuraCast\Customization::class];

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
        $hour_select_utc[$time_num] = $customization->formatTime($hour_timestamp, false, true).' ('.$customization->formatTime($hour_timestamp, true, true).')';
    }
}

// Sort select so 12:00AM appears at the top.
ksort($local_to_utc, \SORT_NUMERIC);

$hour_select = [];
foreach($local_to_utc as $local_time => $utc_time) {
    $hour_select[$utc_time] = $hour_select_utc[$utc_time];
}

$server_time = sprintf(_('Your current local time is <b>%s</b>. You can customize your time zone from the "My Account" page.'), $customization->formatTime());

return [
    'method' => 'post',
    'enctype' => 'multipart/form-data',

    'groups' => [

        'basic_info' => [
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => _('Playlist Name'),
                        'required' => true,
                    ]
                ],

                'is_enabled' => [
                    'radio',
                    [
                        'label' => _('Enable Playlist'),
                        'required' => true,
                        'description' => _('If set to "No", the playlist will not be included in radio playback, but can still be managed.'),
                        'options' => [
                            1 => _('Yes'),
                            0 => _('No'),
                        ],
                        'default' => 1,
                    ]
                ],

                'import' => [
                    'file',
                    [
                        'label' => _('Import Existing Playlist'),
                        'description' => _('Select an existing playlist file to add its contents to this playlist. PLS and M3U are supported.'),
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
                        'label' => _('Playlist Weight'),
                        'description' => _('Higher weight playlists are played more frequently compared to other lower-weight playlists.'),
                        'default' => 3,
                        'required' => true,
                        'options' => [
                            1 => '1 - '._('Low'),
                            2 => '2',
                            3 => '3 - '._('Default'),
                            4 => '4',
                            5 => '5 - '._('High'),
                        ] + \App\Utilities::pairs(range(6, 25)),
                    ]
                ],

                'type' => [
                    'radio',
                    [
                        'label' => _('Playlist Type'),
                        'options' => [
                            'default' => '<b>' . _('Standard Playlist') . ':</b> ' . _('Plays all day, shuffles with other standard playlists based on weight.'),
                            'scheduled' => '<b>' . _('Scheduled Playlist') . ':</b> ' . _('Play during a scheduled time range. Useful for mood-based time playlists.'),
                            'once_per_x_songs' => '<b>' . _('Once per x Songs Playlist') . ':</b> ' . _('Play exactly once every <i>x</i> songs. Useful for station ID/jingles.'),
                            'once_per_x_minutes' => '<b>' . _('Once Per x Minutes Playlist') . ':</b> ' . _('Play exactly once every <i>x</i> minutes. Useful for station ID/jingles.'),
                            'once_per_day' => '<b>' . _('Daily Playlist') . '</b>: ' . _('Play once per day at the specified time. Useful for timely reminders.'),
                            'custom' => '<b>' . _('Custom Playlist') .'</b>: ' . _('Manually define how this playlist is used in Liquidsoap configuration. For advanced users only!'),
                        ],
                        'default' => 'default',
                        'required' => true,
                    ]
                ],

            ],
        ],

        'type_default' => [
            'legend' => _('Standard Playlist'),
            'class' => 'type_fieldset',
            'elements' => [

                'include_in_automation' => [
                    'radio',
                    [
                        'label' => _('Include in Automated Assignment'),
                        'description' => _('If auto-assignment is enabled, use this playlist as one of the targets for songs to be redistributed into. This will overwrite the existing contents of this playlist.'),
                        'required' => true,
                        'default' => '0',
                        'options' => [
                            0 => _('No'),
                            1 => _('Yes'),
                        ],
                    ]
                ],

            ],
        ],

        'type_scheduled' => [
            'legend' => _('Scheduled Playlist'),
            'class' => 'type_fieldset',
            'elements' => [

                'schedule_start_time' => [
                    'select',
                    [
                        'label' => _('Start Time'),
                        'description' => $server_time,
                        'options' => $hour_select,
                    ]
                ],

                'schedule_end_time' => [
                    'select',
                    [
                        'label' => _('End Time'),
                        'description' => _('If the end time is before the start time, the playlist will play overnight until this time on the next day.'),
                        'options' => $hour_select,
                    ]
                ],

                'schedule_days' => [
                    'checkbox',
                    [
                        'label' => _('Scheduled Play Days of Week'),
                        'description' => _('Leave blank to play on every day of the week.'),
                        'options' => [
                            1 => _('Monday'),
                            2 => _('Tuesday'),
                            3 => _('Wednesday'),
                            4 => _('Thursday'),
                            5 => _('Friday'),
                            6 => _('Saturday'),
                            7 => _('Sunday'),
                        ]
                    ]
                ],

            ],
        ],

        'type_once_per_x_songs' => [
            'legend' => _('Once per x Songs Playlist'),
            'class' => 'type_fieldset',
            'elements' => [

                'play_per_songs' => [
                    'select',
                    [
                        'label' => _('Number of Songs Between Plays'),
                        'description' => _('This playlist will play every $x songs, where $x is specified below.'),
                        'options' => \App\Utilities::pairs(range(1, 100)),
                    ]
                ],

            ],
        ],

        'type_once_per_x_minutes' => [
            'legend' => _('Once per x Minutes Playlist'),
            'class' => 'type_fieldset',
            'elements' => [

                'play_per_minutes' => [
                    'select',
                    [
                        'label' => _('Number of Minutes Between Plays'),
                        'description' => _('This playlist will play every $x minutes, where $x is specified below.'),
                        'options' => \App\Utilities::pairs(range(1, 240)),
                    ]
                ],

            ],
        ],

        'type_once_per_day' => [
            'legend' => _('Daily Playlist'),
            'class' => 'type_fieldset',
            'elements' => [

                'play_once_time' => [
                    'select',
                    [
                        'label' => _('Scheduled Play Time'),
                        'description' => $server_time,
                        'options' => $hour_select,
                    ]
                ],

                'play_once_days' => [
                    'checkbox',
                    [
                        'label' => _('Scheduled Play Days of Week'),
                        'description' => _('Leave blank to play on every day of the week.'),
                        'options' => [
                            1 => _('Monday'),
                            2 => _('Tuesday'),
                            3 => _('Wednesday'),
                            4 => _('Thursday'),
                            5 => _('Friday'),
                            6 => _('Saturday'),
                            7 => _('Sunday'),
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
                        'label' => _('Save Changes'),
                        'class' => 'ui-button btn-lg btn-primary',
                    ]
                ],

            ],
        ],
    ],
];