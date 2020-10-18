<?php

return [
    'method' => 'post',
    'groups' => [
        [
            'use_grid' => true,
            'elements' => [

                'name' => [
                    'text',
                    [
                        'label' => __('Field Name'),
                        'description' => __('This will be used as the label when editing individual songs, and will show in API results.'),
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'short_name' => [
                    'text',
                    [
                        'label' => __('Programmatic Name'),
                        'description' => __('Optionally specify an API-friendly name, such as <code>field_name</code>. Leave this field blank to automatically create one based on the name.'),
                        'form_group_class' => 'col-md-6',
                    ],
                ],

                'auto_assign' => [
                    'select',
                    [
                        'label' => __('Automatically Set from ID3v2 Value'),
                        'description' => __('Optionally select an ID3v2 metadata field that, if present, will be used to set this field\'s value.'),
                        'default' => '',
                        'form_group_class' => 'col-md-6',
                        'options' => [
                            '' => __('Disable'),
                            'album' => __('Album'), // TAL, TALB
                            'album_artist_sort_order' => __('Album Artist Sort Order'), // TS2, TSO2
                            'album_sort_order' => __('Album Sort Order'), // TSA, TSOA
                            'artist' => __('Artist'), // TP1, TPE1
                            'band' => __('Band'), // TP2, TPE2
                            'bpm' => __('Bpm'), // TBP, TBPM
                            'comment' => __('Comment'), // COM, COMM
                            'commercial_information' => __('Commercial Information'), // WCM, WCOM
                            'composer' => __('Composer'), // TCM, TCOM
                            'composer_sort_order' => __('Composer Sort Order'), // TSC, TSOC
                            'conductor' => __('Conductor'), // TP3, TPE3
                            'content_group_description' => __('Content Group Description'), // TIT1, TT1
                            'copyright' => __('Copyright'), // WCOP, WCP
                            'copyright_message' => __('Copyright Message'), // TCOP, TCR
                            'encoded_by' => __('Encoded By'), // TEN, TENC
                            'encoder_settings' => __('Encoder Settings'), // TSS, TSSE
                            'encoding_time' => __('Encoding Time'), // TDEN
                            'file_owner' => __('File Owner'), // TOWN
                            'file_type' => __('File Type'), // TFLT, TFT
                            'genre' => __('Genre'), // TCO, TCON
                            'initial_key' => __('Initial Key'), // TKE, TKEY
                            'internet_radio_station_name' => __('Internet Radio Station Name'), // TRSN
                            'internet_radio_station_owner' => __('Internet Radio Station Owner'), // TRSO
                            'involved_people_list' => __('Involved People List'), // IPL, IPLS, TIPL
                            'isrc' => __('ISRC'), // TRC, TSRC
                            'language' => __('Language'), // TLA, TLAN
                            'length' => __('Length'), // TLE, TLEN
                            'linked_information' => __('Linked Information'), // LINK, LNK
                            'lyricist' => __('Lyricist'), // TEXT, TXT
                            'media_type' => __('Media Type'), // TMED, TMT
                            'mood' => __('Mood'), // TMOO
                            'music_cd_identifier' => __('Music CD Identifier'), // MCDI, MCI
                            'musician_credits_list' => __('Musician Credits List'), // TMCL
                            'original_album' => __('Original Album'), // TOAL, TOT
                            'original_artist' => __('Original Artist'), // TOA, TOPE
                            'original_filename' => __('Original Filename'), // TOF, TOFN
                            'original_lyricist' => __('Original Lyricist'), // TOL, TOLY
                            'original_release_time' => __('Original Release Time'), // TDOR
                            'original_year' => __('Original Year'), // TOR, TORY
                            'part_of_a_compilation' => __('Part Of A Compilation'), // TCMP, TCP
                            'part_of_a_set' => __('Part Of A Set'), // TPA, TPOS
                            'performer_sort_order' => __('Performer Sort Order'), // TSOP, TSP
                            'playlist_delay' => __('Playlist Delay'), // TDLY, TDY
                            'produced_notice' => __('Produced Notice'), // TPRO
                            'publisher' => __('Publisher'), // TPB, TPUB
                            'recording_time' => __('Recording Time'), // TDRC
                            'release_time' => __('Release Time'), // TDRL
                            'remixer' => __('Remixer'), // TP4, TPE4
                            'set_subtitle' => __('Set Subtitle'), // TSST
                            'subtitle' => __('Subtitle'), // TIT3, TT3
                            'tagging_time' => __('Tagging Time'), // TDTG
                            'terms_of_use' => __('Terms Of Use'), // USER
                            'title' => __('Title'), // TIT2, TT2
                            'title_sort_order' => __('Title Sort Order'), // TSOT, TST
                            'track_number' => __('Track Number'), // TRCK, TRK
                            'unsynchronised_lyric' => __('Unsynchronised Lyric'), // ULT, USLT
                            'url_artist' => __('URL Artist'), // WAR, WOAR
                            'url_file' => __('URL File'), // WAF, WOAF
                            'url_payment' => __('URL Payment'), // WPAY
                            'url_publisher' => __('URL Publisher'), // WPB, WPUB
                            'url_source' => __('URL Source'), // WAS, WOAS
                            'url_station' => __('URL Station'), // WORS
                            'url_user' => __('URL User'), // WXX, WXXX
                            'year' => __('Year'), // TYE, TYER
                        ],
                    ],
                ],

                'submit' => [
                    'submit',
                    [
                        'type' => 'submit',
                        'label' => __('Save Changes'),
                        'class' => 'btn btn-lg btn-primary',
                        'form_group_class' => 'col-sm-12',
                    ],
                ],

            ],
        ],
    ],
];
