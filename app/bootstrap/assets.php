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
                '//cdnjs.cloudflare.com/ajax/libs/jquery/2.2.4/jquery.min.js',
            ],
        ],
    ],
    [
        'name' => 'vue',
        'order' => 1,
        'group' => 'header',
        'files' => [
            'js' => [
                '//cdnjs.cloudflare.com/ajax/libs/vue/2.4.4/' . (APP_IN_PRODUCTION ? 'vue.min.js' : 'vue.js'),
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
                '//cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css',
                '//cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css',
            ],
            'js' => [
                'bower_components/bootstrap/dist/js/bootstrap.min.js',
            ]
        ],
        'require' => ['jquery'],
    ],
    [
        'name' => 'minimal_body',
        'order' => 50,
        'group' => 'body',
        'files' => [
            'js' => [
                '//cdnjs.cloudflare.com/ajax/libs/node-waves/0.7.5/waves.min.js',
                '//cdnjs.cloudflare.com/ajax/libs/mouse0270-bootstrap-notify/3.1.7/bootstrap-notify.min.js',
                'js/app.min.js',
            ],
        ],
    ],
    [
        'name' => 'main_header',
        'order' => 3,
        'files' => [
            'css' => [
                '//cdnjs.cloudflare.com/ajax/libs/bootstrap-sweetalert/1.0.1/sweetalert.min.css',
                '//cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css',
            ],
        ],
        'require' => ['minimal_header'],

    ],
    [
        'name' => 'main_body',
        'order' => 49,
        'files' => [
            'js' => [
                '//cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js',
                '//cdnjs.cloudflare.com/ajax/libs/bootstrap-sweetalert/1.0.1/sweetalert.min.js',
                '//cdnjs.cloudflare.com/ajax/libs/autosize.js/4.0.0/autosize.min.js',
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
                'js/bootgrid/jquery.bootgrid.min.css',
            ],
            'js' => [
                'js/bootgrid/jquery.bootgrid.updated.min.js',
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
                '//cdnjs.cloudflare.com/ajax/libs/store.js/1.3.20/store.min.js',
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
        'name' => 'zxcvbn',
        'order' => 10,
        'group' => 'body',
        'files' => [
            'js' => [
                '//cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js',
            ],
        ],
    ],
    [
        'name' => 'chosen',
        'order' => 10,
        'group' => 'body',
        'files' => [
            'js' => [
                '//cdnjs.cloudflare.com/ajax/libs/chosen/1.8.2/chosen.jquery.min.js',
            ],
            'css' => [
                '//cdnjs.cloudflare.com/ajax/libs/chosen/1.8.2/chosen.min.css',
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
                '//cdnjs.cloudflare.com/ajax/libs/codemirror/5.30.0/codemirror.min.js',
                '//cdnjs.cloudflare.com/ajax/libs/codemirror/5.30.0/mode/css/css.min.js',
            ],
            'css' => [
                '//cdnjs.cloudflare.com/ajax/libs/codemirror/5.30.0/codemirror.min.css',
                '//cdnjs.cloudflare.com/ajax/libs/codemirror/5.30.0/theme/material.min.css',
            ],
        ],
    ]
];