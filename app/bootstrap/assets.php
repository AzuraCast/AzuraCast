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
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.4/jquery.min.js',
                    'sri' => 'sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=',
                ],
            ],
        ],
    ],
    [
        'name' => 'vue',
        'order' => 1,
        'group' => 'header',
        'files' => [
            'js' => [
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/vue/2.5.13/' . (APP_IN_PRODUCTION ? 'vue.min.js' : 'vue.js'),
                    'sri' => (APP_IN_PRODUCTION)
                        ? 'sha256-1Q2q5hg2YXp9fYlM++sIEXOcUb8BRSDUsQ1zXvLBqmA='
                        : 'sha256-pU9euBaEcVl8Gtg+FRYCtin2vKLN8sx5/4npZDmY2VA=',
                ],
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
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css',
                    'sri' => 'sha256-j+P6EZJVrbXgwSR5Mx+eCS6FvP9Wq27MBRC/ogVriY0=',
                ],
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css',
                    'sri' => 'sha256-3sPp8BkKUE7QyPSl6VfBByBroQbKxKG7tsusY2mhbVY=',
                ],
            ],
            'js' => [
                [
                    'src' => 'bower_components/bootstrap/dist/js/bootstrap.min.js',
                ],
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
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/node-waves/0.7.5/waves.min.js',
                    'sri' => 'sha256-ICvFZLf7gslwfpvdxzQ8w8oZt0brzoFr8v2dXBecuLY=',
                ],
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/mouse0270-bootstrap-notify/3.1.7/bootstrap-notify.min.js',
                    'sri' => 'sha256-LlN0a0J3hMkDLO1mhcMwy+GIMbIRV7kvKHx4oCxNoxI=',
                ],
                [
                    'src' => 'js/app.min.js',
                ],
            ],
        ],
    ],
    [
        'name' => 'main_header',
        'order' => 3,
        'files' => [
            'css' => [
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-sweetalert/1.0.1/sweetalert.min.css',
                    'sri' => 'sha256-zuyRv+YsWwh1XR5tsrZ7VCfGqUmmPmqBjIvJgQWoSDo=',
                ],
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css',
                    'sri' => 'sha256-JHGEmB629pipTkMag9aMaw32I8zle24p3FpsEeI6oZU=',
                ]
            ],
        ],
        'require' => ['minimal_header'],

    ],
    [
        'name' => 'main_body',
        'order' => 49,
        'files' => [
            'js' => [
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js',
                    'sri' => 'sha256-/YAntTqXy9V4LoXFkI5WPDl3ZwP/knn1BljmMJJ7QWc=',
                ],
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-sweetalert/1.0.1/sweetalert.min.js',
                    'sri' => 'sha256-JirYRqbf+qzfqVtEE4GETyHlAbiCpC005yBTa4rj6xg=',
                ],
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/autosize.js/4.0.0/autosize.min.js',
                    'sri' => 'sha256-F7Bbc+3hGv34D+obsHHsSm3ZKRBudWR7e2H0fS0beok=',
                ],
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
                [
                    'src' => 'css/dark.css',
                ],
            ],
        ]
    ],
    [
        'name' => 'theme_light',
        'order' => 50,
        'group' => 'body',
        'files' => [
            'css' => [
                [
                    'src' => 'css/light.css',
                ],
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
                [
                    'src' => 'js/bootgrid/jquery.bootgrid.min.css',
                ],
            ],
            'js' => [
                [
                    'src' => 'js/bootgrid/jquery.bootgrid.updated.min.js',
                ],
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
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/store.js/1.3.20/store.min.js',
                    'sri' => 'sha256-0jgHNEQo7sIScbcI/Pc5GYJ+VosKM1mJ+fI0iuQ1a9E=',
                ],
                [
                    'src' => 'js/radio.js',
                ],
                [
                    'src' => 'js/nchan.js',
                ],
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
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/highcharts/6.0.4/highcharts.js',
                    'sri' => 'sha256-jLlwSowwSPJ9ukSEWxfqld2rgZTzBcTJhfotyvtdOSk=',
                ],
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/highcharts/6.0.4/highcharts-more.js',
                    'sri' => 'sha256-QnoLQZe7BYRVTl3AY8Lsw6mn60HfHZNpcZBEndybfBk=',
                ],
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/highcharts/6.0.4/js/modules/exporting.js',
                    'sri' => 'sha256-lUeVX+hzn6tYnZ3uT+J5hmfN0K2LAbsvFar6eiKgKMc=',
                ],
            ],
        ]
    ],
    [
        'name' => 'highcharts_theme_dark',
        'order' => 21,
        'group' => 'body',
        'files' => [
            'js' => [
                [
                    'src' => 'js/highcharts/dark-blue.js',
                ],
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
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js',
                    'sri' => 'sha256-Znf8FdJF85f1LV0JmPOob5qudSrns8pLPZ6qkd/+F0o=',
                ],
            ],
        ],
    ],
    [
        'name' => 'chosen',
        'order' => 10,
        'group' => 'body',
        'files' => [
            'js' => [
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.2/chosen.jquery.min.js',
                    'sri' => 'sha256-j9yXOqKOlGKkAerTz/6KCllekmWP3Kt3a7sBvMK8IGI=',
                ],
            ],
            'css' => [
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.2/chosen.min.css',
                    'sri' => 'sha256-mmiAhiWsn5EjME5u13M5klIesdx2mQQnvwSDFWwAW4E=',
                ],
            ]
        ],
    ],
    [
        'name' => 'daterangepicker',
        'order' => 9,
        'group' => 'body',
        'files' => [
            'js' => [
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.20.1/moment.min.js',
                    'sri' => 'sha256-ABVkpwb9K9PxubvRrHMkk6wmWcIHUE9eBxNZLXYQ84k=',
                ],
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/2.1.27/daterangepicker.min.js',
                    'sri' => 'sha256-fuPJ7xvV6OPcIGSJd2Xj7s/+2aWsVGapv+Uj/cuVOzk=',
                ],
            ],
            'css' => [
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/2.1.27/daterangepicker.min.css',
                    'sri' => 'sha256-m4uCSkjNdbrhPh2GPVsyB8nuDl5uiF/DpAhSGdqujrc=',
                ],
            ],
        ]
    ],
    [
        'name' => 'codemirror_css',
        'order' => 10,
        'group' => 'body',
        'files' => [
            'js' => [
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.33.0/codemirror.min.js',
                    'sri' => 'sha256-ag7KgA1S7cuuU2FCC2G7/L8IpaijDSPqzcLLLeJv5Iw=',
                ],
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.33.0/mode/css/css.min.js',
                    'sri' => 'sha256-EPuuMaFXpkGuc1TQeBblqQDxuPiTFgd8K+l/vGIC5EQ=',
                ],
            ],
            'css' => [
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.33.0/codemirror.min.css',
                    'sri' => 'sha256-I8NyGs4wjbMuBSUE40o55W6k6P7tu/7G28/JGUUYCIs=',
                ],
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.33.0/theme/material.min.css',
                    'sri' => 'sha256-UyTiM5wwtuGiISIGyvkdYa9kgCRJmBQ+OYU72oexofc=',
                ],
            ],
        ],
    ]
];