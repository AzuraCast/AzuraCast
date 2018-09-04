<?php
use \App\Entity\StationPlaylist;

/** @var \App\Customization $customization */

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
                        'choices' => [
                            1 => __('Yes'),
                            0 => __('No'),
                        ],
                        'default' => 1,
                    ]
                ],

                'weight' => [
                    'select',
                    [
                        'label' => __('Playlist Weight'),
                        'description' => __('Higher weight playlists are played more frequently compared to other lower-weight playlists.'),
                        'default' => 3,
                        'required' => true,
                        'choices' => [
                            1 => '1 - '.__('Low'),
                            2 => '2',
                            3 => '3 - '.__('Default'),
                            4 => '4',
                            5 => '5 - '.__('High'),
                        ] + \App\Utilities::pairs(range(6, 25)),
                    ]
                ],

                'source' => [
                    'radio',
                    [
                        'label' => __('Source'),
                        'choices' => [
                            StationPlaylist::SOURCE_SONGS => '<b>' . __('Song-Based Playlist') .':</b> ' . __('A playlist containing media files hosted on this server.'),
                            StationPlaylist::SOURCE_REMOTE_URL => '<b>'.__('Remote URL Playlist').':</b> ' . __('A playlist that instructs the station to play from a remote URL.'),
                        ],
                        'default' => StationPlaylist::SOURCE_SONGS,
                        'required' => true,
                    ]
                ],

                'type' => [
                    'radio',
                    [
                        'label' => __('Scheduling'),
                        'choices' => [
                            StationPlaylist::TYPE_DEFAULT => '<b>' . __('General Rotation') . ':</b> ' . __('Plays all day, shuffles with other standard playlists based on weight.'),
                            StationPlaylist::TYPE_SCHEDULED => '<b>' . __('Scheduled') . ':</b> ' . __('Play during a scheduled time range. Useful for mood-based time playlists.'),
                            StationPlaylist::TYPE_ONCE_PER_X_SONGS => '<b>' . __('Once per x Songs') . ':</b> ' . __('Play exactly once every <i>x</i> songs. Useful for station ID/jingles.'),
                            StationPlaylist::TYPE_ONCE_PER_X_MINUTES => '<b>' . __('Once Per x Minutes') . ':</b> ' . __('Play exactly once every <i>x</i> minutes. Useful for station ID/jingles.'),
                            StationPlaylist::TYPE_ONCE_PER_DAY => '<b>' . __('Daily') . '</b>: ' . __('Play once per day at the specified time. Useful for timely reminders.'),
                            StationPlaylist::TYPE_ADVANCED => '<b>' . __('Advanced') .'</b>: ' . __('Manually define how this playlist is used in Liquidsoap configuration. <a href="%s" target="_blank">Learn about Advanced Playlists</a>', 'https://github.com/AzuraCast/AzuraCast/wiki/Using-Advanced-Playlists'),
                        ],
                        'default' => StationPlaylist::TYPE_DEFAULT,
                        'required' => true,
                    ]
                ],

            ],
        ],

        'source_'.StationPlaylist::SOURCE_SONGS => [
            'legend' => __('Song-Based Playlist'),
            'class' => 'source_fieldset',
            'elements' => [

                'order' => [
                    'radio',
                    [
                        'label' => __('Song Playback Order'),
                        'required' => true,
                        'choices' => [
                            StationPlaylist::ORDER_SHUFFLE => __('Shuffled'),
                            StationPlaylist::ORDER_RANDOM => __('Random'),
                            StationPlaylist::ORDER_SEQUENTIAL => __('Sequential'),
                        ],
                        'default' => StationPlaylist::ORDER_SHUFFLE,
                    ],
                ],

                'include_in_requests' => [
                    'radio',
                    [
                        'label' => __('Allow Requests from This Playlist'),
                        'required' => true,
                        'description' => __('If requests are enabled for your station, users will be able to request media that is on this playlist.'),
                        'choices' => [
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

            ],
        ],

        'source_'.StationPlaylist::SOURCE_REMOTE_URL => [
            'legend' => __('Remote URL Playlist'),
            'class' => 'source_fieldset',
            'elements' => [

                'remote_url' => [
                    'text',
                    [
                        'label' => __('Remote URL'),
                    ]
                ],

            ]
        ],

        'type_default' => [
            'legend' => __('General Rotation'),
            'class' => 'type_fieldset',
            'elements' => [

                'include_in_automation' => [
                    'radio',
                    [
                        'label' => __('Include in Automated Assignment'),
                        'description' => __('If auto-assignment is enabled, use this playlist as one of the targets for songs to be redistributed into. This will overwrite the existing contents of this playlist.'),
                        'required' => true,
                        'default' => '0',
                        'choices' => [
                            0 => __('No'),
                            1 => __('Yes'),
                        ],
                    ]
                ],

            ],
        ],

        'type_'.StationPlaylist::TYPE_SCHEDULED => [
            'legend' => __('Customize Schedule'),
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
                        'choices' => [
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

        'type_'.StationPlaylist::TYPE_ONCE_PER_X_SONGS => [
            'legend' => __('Once per x Songs'),
            'class' => 'type_fieldset',
            'elements' => [

                'play_per_songs' => [
                    'number',
                    [
                        'label' => __('Number of Songs Between Plays'),
                        'description' => __('This playlist will play every $x songs, where $x is specified below.'),
                        'default' => 1,
                        'step' => 1,
                        'min' => 0,
                        'max' => 150,
                        'filter' => function($val) {
                            return (int)$val;
                        }
                    ]
                ],

            ],
        ],

        'type_'.StationPlaylist::TYPE_ONCE_PER_X_MINUTES => [
            'legend' => __('Once per x Minutes'),
            'class' => 'type_fieldset',
            'elements' => [

                'play_per_minutes' => [
                    'number',
                    [
                        'label' => __('Number of Minutes Between Plays'),
                        'description' => __('This playlist will play every $x minutes, where $x is specified below.'),
                        'default' => 1,
                        'step' => 1,
                        'min' => 0,
                        'max' => 120,
                        'filter' => function($val) {
                            return (int)$val;
                        }
                    ]
                ],

            ],
        ],

        'type_'.StationPlaylist::TYPE_ONCE_PER_DAY => [
            'legend' => __('Daily'),
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
                        'choices' => [
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
