<?php
use App\Http\Request;

/**
 * Static assets referenced in AzuraCast.
 * Stored here to easily resolve dependencies on individual pages.
 */

return [
    /*
     * Core libraries
     */
    'jquery' => [
        'order' => 0,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/jquery/jquery.min.js',
                ],
            ],
        ],
    ],

    'vue' => [
        'order' => 1,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/vue/' . (APP_IN_PRODUCTION ? 'vue.min.js' : 'vue.js'),
                ],
            ],
        ],
    ],

    'vue-i18n' => [
        'order' => 2,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/vue-i18n/vue-i18n.min.js',
                ],
            ],
        ],
    ],

    'lodash' => [
        'order' => 2,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/lodash/lodash.min.js',
                ]
            ]
        ]
    ],

    /*
     * Main per-layout dependencies
     */
    'minimal' => [
        'order' => 2,
        'require' => ['jquery'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/bootstrap/bootstrap.bundle.min.js',
                ],
                [
                    'src' => 'dist/lib/bootstrap-notify/bootstrap-notify.min.js',
                    'defer' => true,
                ],
                [
                    'src' => 'dist/app.js',
                    'defer' => true,
                ],
                [
                    'src' => 'dist/material.js',
                ]
            ]
        ],
    ],

    'main' => [
        'order' => 3,
        'require' => ['minimal'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/sweetalert/sweetalert.min.js',
                    'defer' => true,
                ],
                [
                    'src' => 'dist/lib/autosize/autosize.min.js',
                    'defer' => true,
                ],
            ],
        ],
    ],

    /*
     * Themes
     */
    'theme_dark' => [
        'order' => 50,
        'files' => [
            'css' => [
                [
                    'href' => 'dist/dark.css',
                ],
            ],
        ]
    ],
    'theme_light' => [
        'order' => 50,
        'files' => [
            'css' => [
                [
                    'href' => 'dist/light.css',
                ],
            ],
        ]
    ],

    /*
     * Individual libraries
     */
    'bootgrid' => [
        'order' => 8,
        'require' => ['jquery'],
        'files' => [
            'css' => [
                [
                    'href' => 'js/bootgrid/jquery.bootgrid.min.css',
                ],
            ],
            'js' => [
                [
                    'src' => 'js/bootgrid/jquery.bootgrid.updated.js',
                ],
                [
                    'src' => 'dist/bootgrid.js',
                ],
            ],
        ],
    ],
    'radio' => [
        'order' => 20,
        'require' => ['jquery'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/store/store.min.js',
                    'defer' => true,
                ],
                [
                    'src' => 'dist/radio.js',
                    'defer' => true,
                ],
            ],
        ],

    ],
    'highcharts' => [
        'order' => 20,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/highcharts/highcharts.js',
                    'defer' => true,
                ],
                [
                    'src' => 'dist/lib/highcharts/highcharts-more.js',
                    'defer' => true,
                ],
                [
                    'src' => 'dist/lib/highcharts/exporting.js',
                    'defer' => true,
                ],
            ]
        ]
    ],
    'highmaps' => [
        'order' => 22,
        'require' => ['jquery', 'highcharts'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/proj4/proj4.js',
                    'defer' => true,
                ],
                [
                    'src' => 'dist/lib/highcharts/map.js',
                    'defer' => true,
                ],
                [
                    'src' => 'js/highmaps/world.js',
                    'defer' => true,
                ],
            ]
        ],
    ],
    'highcharts_theme_dark' => [
        'order' => 21,
        'files' => [
            'js' => [
                [
                    'src' => 'js/highcharts/dark-blue.js',
                    'defer' => true,
                ],
            ]
        ]
    ],
    'highcharts_theme_light' => [], //empty placeholder

    'zxcvbn' => [
        'order' => 10,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/zxcvbn/zxcvbn.js',
                    'defer' => true,
                ],
                [
                    'src' => 'dist/zxcvbn.js',
                    'defer' => true,
                ],
            ],
        ],
    ],

    'chosen' => [
        'order' => 10,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/chosen/chosen.jquery.min.js',
                    'defer' => true,
                ],
            ],
            'css' => [
                [
                    'href' => 'dist/lib/chosen/chosen.min.css',
                ],
            ]
        ],
    ],

    'moment' => [
        'order' => 8,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/moment/moment-with-locales.min.js',
                    'charset' => 'UTF-8',
                ],
                [
                    'src' => 'dist/lib/moment-timezone/moment-timezone-with-data.min.js',
                ],
            ]
        ],
        'inline' => [
            'js' => [
                function(Request $request) {
                    if (!$request->hasAttribute('timezone')) {
                        return '';
                    }

                    $tz = $request->getAttribute('timezone');
                    $locale = str_replace('_', '-', explode('.', $request->getAttribute('locale'))[0]);

                    return 'moment.tz.setDefault('.json_encode($tz).');'."\n"
                        .'moment.locale('.json_encode($locale).');';
                },
            ],
        ],
    ],

    'daterangepicker' => [
        'order' => 9,
        'require' => ['moment'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/daterangepicker/daterangepicker.js',
                    'defer' => true,
                ],
            ],
            'css' => [
                [
                    'href' => 'dist/lib/daterangepicker/daterangepicker.css',
                ],
            ],
        ],
    ],

    'codemirror_css' => [
        'order' => 10,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/codemirror/codemirror.js',
                    'defer' => true,
                ],
                [
                    'src' => 'dist/lib/codemirror/css.js',
                    'defer' => true,
                ],
            ],
            'css' => [
                [
                    'href' => 'dist/lib/codemirror/codemirror.css',
                ],
                [
                    'href' => 'dist/lib/codemirror/material.css',
                ],
            ],
        ],
    ],

    'clipboard' => [
        'order' => 10,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/clipboard/clipboard.min.js',
                ],
            ],
        ],
        'inline' => [
            'js' => [
                "new ClipboardJS('.btn-copy');",
            ],
        ],
    ],

    'fancybox' => [
        'order' => 10,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/fancybox/jquery.fancybox.min.js',
                    'defer' => true,
                ],
            ],
            'css' => [
                [
                    'href' => 'dist/lib/fancybox/jquery.fancybox.min.css',
                ]
            ],
        ],
    ],

    'flowjs' => [
        'order' => 10,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/flowjs/flow.min.js',
                    'defer' => true,
                ],
            ],
        ],
    ],

    'fullcalendar' => [
        'order' => 10,
        'require' => ['moment'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/fullcalendar/fullcalendar.min.js',
                    'defer' => true,
                ],
                [
                    'src' => 'dist/lib/fullcalendar/locale-all.js',
                    'defer' => true,
                ],
            ],
            'css' => [
                [
                    'href' => 'dist/lib/fullcalendar/fullcalendar.min.css',
                ]
            ]
        ],
    ],

    'jquery-sortable' => [
        'order' => 10,
        'require' => ['jquery'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/jquery-sortable/jquery-sortable-min.js',
                    'defer' => true,
                ],
            ],
        ],
    ],

    'webcaster' => [
        'order' => 10,
        'require' => ['vue', 'vue-i18n', 'lodash'],
        'files' => [
            'js' => [
                [
                    'src' => 'https://cdn.rawgit.com/toots/shine/master/js/dist/libshine.js',
                ],
                [
                    'src' => 'https://cdn.rawgit.com/webcast/libsamplerate.js/master/dist/libsamplerate.js',
                ],
                [
                    'src' => 'https://cdn.rawgit.com/webcast/taglib.js/master/dist/taglib.js',
                ],
                [
                    'src' => 'https://cdn.rawgit.com/webcast/webcast.js/master/lib/webcast.js',
                ],
                [
                    'src' => 'dist/webcaster.js',
                ],
            ]
        ]
    ],

    'radio_player' => [
        'order' => 10,
        'require' => ['vue', 'vue-i18n'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/radio_player.js',
                ],
            ]
        ]
    ],
];
