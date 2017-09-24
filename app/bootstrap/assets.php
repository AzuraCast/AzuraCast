<?php
/**
 * Static assets referenced in AzuraCast.
 * Stored here to easily resolve dependencies on individual pages.
 */

return [
    /*
     * Core libraries
     */
    [
        'name' => 'jquery',
        'order' => 0,
        'group' => 'header',
        'files' => [
            'js' => [
                'bower_components/jquery/dist/jquery.min.js',
            ],
        ],
    ],
    [
        'name' => 'vue',
        'order' => 1,
        'group' => 'header',
        'files' => [
            'js' => [
                'bower_components/vue/dist/' . (APP_IN_PRODUCTION ? 'vue.min.js' : 'vue.js'),
            ],
        ],
    ],

    /*
     * Main per-layout dependencies
     */
    [
        'name' => 'minimal_header',
        'order' => 2,
        'group' => 'header',
        'files' => [
            'css' => [
                'bower_components/animate.css/animate.min.css',
                'bower_components/material-design-iconic-font/dist/css/material-design-iconic-font.min.css',
            ],
            'js' => [
                'bower_components/bootstrap/dist/js/bootstrap.min.js',
            ]
        ],
        'require' => ['jquery'],
    ],
    [
        'name' => 'minimal_body',
        'order' => 2,
        'group' => 'body',
        'files' => [
            'js' => [
                'bower_components/Waves/dist/waves.min.js',
                'bower_components/remarkable-bootstrap-notify/dist/bootstrap-notify.min.js',
                'js/app.min.js',
            ],
        ],
    ],
    [
        'name' => 'main_header',
        'order' => 3,
        'files' => [
            'css' => [
                'bower_components/bootstrap-sweetalert/lib/sweet-alert.css',
                'bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.min.css',
            ],
        ],
        'require' => ['minimal_header'],

    ],
    [
        'name' => 'main_body',
        'order' => 3,
        'files' => [
            'js' => [
                'bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.concat.min.js',
                'bower_components/bootstrap-sweetalert/lib/sweet-alert.min.js',
                'bower_components/autosize/dist/autosize.js',
            ],
        ],
        'require' => ['minimal_body'],
    ],

    /*
     * Themes
     */
    [
        'name' => 'theme_dark',
        'order' => 50,
        'group' => 'body',
        'files' => [
            'css' => [
                'css/dark.css'
            ],
        ]
    ],
    [
        'name' => 'theme_light',
        'order' => 50,
        'group' => 'body',
        'files' => [
            'css' => [
                'css/light.css'
            ],
        ]
    ],

    /*
     * Individual libraries
     */
    [
        'name' => 'bootgrid',
        'order' => 8,
        'group' => 'body',
        'files' => [
            'css' => [
                'bower_components/jquery.bootgrid/dist/jquery.bootgrid.min.css',
            ],
            'js' => [
                'bower_components/jquery.bootgrid/dist/jquery.bootgrid.min.js',
            ],
        ],
        'require' => ['jquery'],
    ],
    [
        'name' => 'radio',
        'order' => 20,
        'group' => 'body',
        'files' => [
            'js' => [
                'bower_components/store-js/store.min.js',
                'js/radio.js',
                'js/nchan.js',
            ],
        ],
        'require' => ['jquery'],
    ],
    [
        'name' => 'highcharts',
        'order' => 20,
        'group' => 'body',
        'files' => [
            'js' => [
                '//code.highcharts.com/highcharts.js',
                '//code.highcharts.com/highcharts-more.js',
                '//code.highcharts.com/modules/exporting.js',
            ],
        ]
    ],
    [
        'name' => 'highcharts_theme_dark',
        'order' => 21,
        'group' => 'body',
        'files' => [
            'js' => [
                'js/highcharts/dark-blue.js',
            ]
        ]
    ],
    [
        'name' => 'highcharts_theme_light',
        // empty
    ],
    [
        'name' => 'forms',
        'order' => 9,
        'group' => 'body',
        'files' => [
            'js' => [
                'bower_components/chosen/chosen.jquery.min.js',
                'bower_components/zxcvbn/dist/zxcvbn.js',
            ],
            'css' => [
                'bower_components/chosen/chosen.min.css',
            ]
        ],
    ],
    [
        'name' => 'daterangepicker',
        'order' => 9,
        'group' => 'body',
        'files' => [
            'js' => [
                '//cdn.jsdelivr.net/momentjs/latest/moment.min.js',
                '//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js',
            ],
            'css' => [
                '//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.css',
            ],
        ]
    ],
    [
        'name' => 'codemirror_css',
        'order' => 10,
        'group' => 'body',
        'files' => [
            'js' => [
                'bower_components/codemirror/lib/codemirror.js',
                'bower_components/codemirror/mode/css/css.js',
            ],
            'css' => [
                'bower_components/codemirror/lib/codemirror.css',
                'bower_components/codemirror/theme/material.css',
            ],
        ],
    ]
];