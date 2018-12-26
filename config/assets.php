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
                    'src' => 'vendor/jquery/2.2.4/jquery.min.js',
                ],
            ],
        ],
    ],

    'vue' => [
        'order' => 1,
        'files' => [
            'js' => [
                [
                    'src' => 'vendor/vue/2.5.21/' . (APP_IN_PRODUCTION ? 'vue.min.js' : 'vue.js'),
                ],
            ],
        ],
    ],

    'vue-i18n' => [
        'order' => 2,
        'files' => [
            'js' => [
                [
                    'src' => 'vendor/vue-i18n/8.6.0/vue-i18n.min.js',
                ],
            ],
        ],
    ],

    'lodash' => [
        'order' => 2,
        'files' => [
            'js' => [
                [
                    'src' => 'vendor/lodash/4.17.11/lodash.min.js',
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
                    'src' => 'vendor/popper.js/1.14.6/umd/popper.min.js',
                ],
                [
                    'src' => 'vendor/bootstrap/4.2.1/js/bootstrap.min.js',
                ],
                [
                    'src' => 'vendor/bootstrap-notify/3.1.3/bootstrap-notify.min.js',
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
                    'src' => 'vendor/sweetalert/2.1.2/sweetalert.min.js',
                    'defer' => true,
                ],
                [
                    'src' => 'vendor/autosize/4.0.2/autosize.min.js',
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
                    'src' => 'vendor/store/1.3.20/store.min.js',
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
                    'src' => 'vendor/highcharts/7.0.1/highcharts.js',
                    'defer' => true,
                ],
                [
                    'src' => 'vendor/highcharts/7.0.1/highcharts-more.js',
                    'defer' => true,
                ],
                [
                    'src' => 'vendor/highcharts/7.0.1/modules/exporting.js',
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
                    'src' => 'vendor/proj4/2.5.0/proj4.js',
                    'defer' => true,
                ],
                [
                    'src' => 'vendor/highcharts/7.0.1/modules/map.js',
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
                    'src' => 'vendor/zxcvbn/4.4.2/zxcvbn.js',
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
                    'src' => 'vendor/chosen-js/1.8.7/chosen.jquery.min.js',
                    'defer' => true,
                ],
            ],
            'css' => [
                [
                    'href' => 'vendor/chosen-js/1.8.7/chosen.min.css',
                ],
            ]
        ],
    ],

    'moment' => [
        'order' => 8,
        'files' => [
            'js' => [
                [
                    'src' => 'vendor/moment/2.23.0/moment-with-locales.min.js',
                    'charset' => 'UTF-8',
                ],
                [
                    'src' => 'vendor/moment-timezone/0.5.23/moment-timezone-with-data.min.js',
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
                    'src' => 'vendor/bootstrap-daterangepicker/3.0.3/daterangepicker.js',
                    'defer' => true,
                ],
            ],
            'css' => [
                [
                    'href' => 'vendor/bootstrap-daterangepicker/3.0.3/daterangepicker.css',
                ],
            ],
        ],
    ],

    'codemirror_css' => [
        'order' => 10,
        'files' => [
            'js' => [
                [
                    'src' => 'vendor/codemirror/5.42.2/codemirror.js',
                    'defer' => true,
                ],
                [
                    'src' => 'vendor/codemirror/5.42.2/mode/css/css.js',
                    'defer' => true,
                ],
            ],
            'css' => [
                [
                    'href' => 'vendor/codemirror/5.42.2/codemirror.css',
                ],
                [
                    'href' => 'vendor/codemirror/5.42.2/theme/material.css',
                ],
            ],
        ],
    ],

    'clipboard' => [
        'order' => 10,
        'files' => [
            'js' => [
                [
                    'src' => 'vendor/clipboard/2.0.4/clipboard.min.js',
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
                    'src' => 'vendor/@fancyapps/fancybox/3.5.6/jquery.fancybox.min.js',
                    'defer' => true,
                ],
            ],
            'css' => [
                [
                    'href' => 'vendor/@fancyapps/fancybox/3.5.6/jquery.fancybox.min.css',
                ]
            ],
        ],
    ],

    'flowjs' => [
        'order' => 10,
        'files' => [
            'js' => [
                [
                    'src' => 'vendor/@flowjs/flow.js/2.13.1/flow.min.js',
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
                    'src' => 'vendor/fullcalendar/3.9.0/fullcalendar.min.js',
                    'defer' => true,
                ],
                [
                    'src' => 'vendor/fullcalendar/3.9.0/locale-all.js',
                    'defer' => true,
                ],
            ],
            'css' => [
                [
                    'href' => 'vendor/fullcalendar/3.9.0/fullcalendar.min.css',
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
                    'src' => 'vendor/jquery-sortable/0.9.13/jquery-sortable-min.js',
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
];
