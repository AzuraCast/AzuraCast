<?php
use \App\Entity\StationPlaylist;

/** @var \App\Customization $customization */

$local_time_offset = \Azura\Timezone::getOffsetMinutes(null);
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
            'legend' => __('Basic Information'),
            'legend_class' => 'd-none',
            'elements' => [
                'is_enabled' => [
                    'toggle',
                    [
                        'label' => __('Enable Playlist'),
                        'required' => true,
                        'description' => __('If set to "No", the playlist will not be included in radio playback, but can still be managed.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => true,
                        'form_group_class' => 'col-sm-12 mt-3',
                    ]
                ],

                'name' => [
                    'text',
                    [
                        'label' => __('Playlist Name'),
                        'required' => true,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-3',
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
                        ] + array_combine(range(6, 25), range(6, 25)),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],
            ],
        ],

        'select_source' => [
            'legend' => __('Source'),
            'legend_class' => 'd-none',
            'elements' => [
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
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],
            ],
        ],

        'source_'.StationPlaylist::SOURCE_SONGS => [
            'legend' => __('Song-Based Playlist'),
            'legend_class' => 'd-none',
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
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ],
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
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                        'button_text' => __('Select File'),
                        'button_icon' => 'cloud_upload'
                    ]
                ],

                'include_in_requests' => [
                    'toggle',
                    [
                        'label' => __('Allow Requests from This Playlist'),
                        'description' => __('If requests are enabled for your station, users will be able to request media that is on this playlist.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => true,
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'is_jingle' => [
                    'toggle',
                    [
                        'label' => __('Hide Metadata from Listeners ("Jingle Mode")'),
                        'label_class' => 'advanced',
                        'description' => __('Enable this setting to prevent metadata from being sent to the AutoDJ for files in this playlist. This is useful if the playlist contains jingles or bumpers.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

            ],
        ],

        'source_'.StationPlaylist::SOURCE_REMOTE_URL => [
            'legend' => __('Remote URL Playlist'),
            'legend_class' => 'd-none',
            'class' => 'source_fieldset',
            'elements' => [

                'remote_url' => [
                    'text',
                    [
                        'label' => __('Remote URL'),
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'remote_type' => [
                    'radio',
                    [
                        'label' => __('Remote URL Type'),
                        'default' => StationPlaylist::REMOTE_TYPE_STREAM,
                        'choices' => [
                            StationPlaylist::REMOTE_TYPE_STREAM => __('Direct Stream URL'),
                            StationPlaylist::REMOTE_TYPE_PLAYLIST => __('Playlist (M3U/PLS) URL'),
                        ],
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'remote_buffer' => [
                    'number',
                    [
                        'label' => __('Remote Playback Buffer (Seconds)'),
                        'label_class' => 'advanced mb-2',
                        'description' => __('The length of playback time that Liquidsoap should buffer when playing this remote playlist. Shorter times may lead to intermittent playback on unstable connections.'),
                        'default' => StationPlaylist::DEFAULT_REMOTE_BUFFER,
                        'min' => 0,
                        'max' => 120,
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

            ]
        ],

        'select_type' => [
            'legend' => __('Scheduling'),
            'legend_class' => 'd-none',
            'elements' => [
                'type' => [
                    'radio',
                    [
                        'label' => __('Scheduling'),
                        'choices' => [
                            StationPlaylist::TYPE_DEFAULT => '<b>' . __('General Rotation') . ':</b> ' . __('Plays all day, shuffles with other standard playlists based on weight.'),
                            StationPlaylist::TYPE_SCHEDULED => '<b>' . __('Scheduled') . ':</b> ' . __('Play during a scheduled time range.'),
                            StationPlaylist::TYPE_ONCE_PER_X_SONGS => '<b>' . __('Once per x Songs') . ':</b> ' . __('Play exactly once every <i>x</i> songs.'),
                            StationPlaylist::TYPE_ONCE_PER_X_MINUTES => '<b>' . __('Once Per x Minutes') . ':</b> ' . __('Play exactly once every <i>x</i> minutes.'),
                            StationPlaylist::TYPE_ONCE_PER_HOUR => '<b>'.__('Once per Hour') . ':</b> '.__('Play once per hour at the specified minute.'),
                            StationPlaylist::TYPE_ONCE_PER_DAY => '<b>' . __('Daily') . '</b>: ' . __('Play once per day at the specified time. Useful for timely reminders.'),
                            StationPlaylist::TYPE_ADVANCED => '<b>' . __('Advanced') .'</b>: ' . __('Manually define how this playlist is used in Liquidsoap configuration. <a href="%s" target="_blank">Learn about Advanced Playlists</a>', 'https://github.com/AzuraCast/azuracast.com/blob/master/AdvancedPlaylists.md'),
                        ],
                        'default' => StationPlaylist::TYPE_DEFAULT,
                        'required' => true,
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

                'backend_options' => [
                    'checkboxes',
                    [
                        'label' => __('AutoDJ Scheduling Options'),
                        'label_class' => 'advanced',
                        'description' => __('Control how this playlist is handled by the AutoDJ software.'),
                        'choices' => [
                            StationPlaylist::OPTION_INTERRUPT_OTHER_SONGS => __('Interrupt other songs to play at scheduled time.'),
                            StationPlaylist::OPTION_LOOP_PLAYLIST_ONCE => __('Only loop through playlist once.'),
                            StationPlaylist::OPTION_PLAY_SINGLE_TRACK => __('Only play one track at scheduled time.'),
                            StationPlaylist::OPTION_MERGE => __('Merge playlist to play as a single track.'),
                        ],
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],
            ]
        ],

        'type_'.StationPlaylist::TYPE_DEFAULT => [
            'legend' => __('General Rotation'),
            'legend_class' => 'd-none',
            'class' => 'type_fieldset',
            'elements' => [

                'include_in_automation' => [
                    'toggle',
                    [
                        'label' => __('Include in Automated Assignment'),
                        'description' => __('If auto-assignment is enabled, use this playlist as one of the targets for songs to be redistributed into. This will overwrite the existing contents of this playlist.'),
                        'selected_text' => __('Yes'),
                        'deselected_text' => __('No'),
                        'default' => false,
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

            ],
        ],

        'type_'.StationPlaylist::TYPE_SCHEDULED => [
            'legend' => __('Customize Schedule'),
            'legend_class' => 'd-none',
            'class' => 'type_fieldset',
            'elements' => [

                'schedule_start_time' => [
                    'select',
                    [
                        'label' => __('Start Time'),
                        'description' => $server_time,
                        'options' => $hour_select,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

                'schedule_end_time' => [
                    'select',
                    [
                        'label' => __('End Time'),
                        'description' => __('If the end time is before the start time, the playlist will play overnight until this time on the next day.'),
                        'options' => $hour_select,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
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
                        ],
                        'form_group_class' => 'col-md-6 mt-3',
                    ]
                ],

            ],
        ],

        'type_'.StationPlaylist::TYPE_ONCE_PER_X_SONGS => [
            'legend' => __('Once per x Songs'),
            'legend_class' => 'd-none',
            'class' => 'type_fieldset',
            'elements' => [

                'play_per_songs' => [
                    'number',
                    [
                        'label' => __('Number of Songs Between Plays'),
                        'description' => __('This playlist will play every $x songs, where $x is specified below.'),
                        'default' => 1,
                        'min' => 0,
                        'max' => 150,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

            ],
        ],

        'type_'.StationPlaylist::TYPE_ONCE_PER_X_MINUTES => [
            'legend' => __('Once per x Minutes'),
            'legend_class' => 'd-none',
            'class' => 'type_fieldset',
            'elements' => [

                'play_per_minutes' => [
                    'number',
                    [
                        'label' => __('Number of Minutes Between Plays'),
                        'description' => __('This playlist will play every $x minutes, where $x is specified below.'),
                        'default' => 1,
                        'min' => 0,
                        'max' => 360,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

            ]
        ],

        'type_'.StationPlaylist::TYPE_ONCE_PER_HOUR => [
            'legend' => __('Once per Hour'),
            'legend_class' => 'd-none',
            'class' => 'type_fieldset',
            'elements' => [

                'play_per_hour_minute' => [
                    'number',
                    [
                        'label' => __('Minute of Hour to Play'),
                        'description' => __('Specify the minute of every hour that this playlist should play.'),
                        'default' => 0,
                        'min' => 0,
                        'max' => 59,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
                    ]
                ],

            ]
        ],

        'type_'.StationPlaylist::TYPE_ONCE_PER_DAY => [
            'legend' => __('Daily'),
            'legend_class' => 'd-none',
            'class' => 'type_fieldset',
            'elements' => [

                'play_once_time' => [
                    'select',
                    [
                        'label' => __('Scheduled Play Time'),
                        'description' => $server_time,
                        'options' => $hour_select,
                        'label_class' => 'mb-2',
                        'form_group_class' => 'col-md-6 mt-1',
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
                        ],
                        'form_group_class' => 'col-md-6 mt-1',
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
                        'form_group_class' => 'col-sm-12',
                    ]
                ],

            ],
        ],
    ],
];
