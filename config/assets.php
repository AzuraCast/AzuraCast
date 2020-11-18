<?php

use App\Customization;
use App\Settings;
use Psr\Http\Message\ServerRequestInterface as Request;

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
                    'src' => 'dist/lib/vue/' . (Settings::getInstance()->isProduction() ? 'vue.min.js' : 'vue.js'),
                ],
            ],
        ],
        'inline' => [
            'js' => [
                'Vue.prototype.$eventHub = new Vue();',
            ],
        ],
    ],

    'vue-translations' => [
        'order' => 4,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/VueTranslations.js',
                ],
            ],
        ],
        'inline' => [
            'js' => [
                function (Request $request) {
                    return 'VueTranslations.default(App.locale);';
                },
            ],
        ],
    ],

    'bootstrap-vue' => [
        'order' => 3,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/bootstrap-vue/bootstrap-vue.min.js',
                ],
            ],
            'css' => [
                [
                    'href' => 'dist/lib/bootstrap-vue/bootstrap-vue.min.css',
                ],
            ],
        ],
    ],

    'vue-component-common' => [
        'order' => 3,
        'require' => ['vue', 'vue-translations'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/vendor.js',
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
                ],
            ],
        ],
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
                ],
            ],
            'css' => [
                [
                    'href' => 'dist/lib/roboto-fontface/css/roboto/roboto-fontface.css',
                ],
                [
                    'href' => 'dist/lib/material-icons/material-icons.css',
                ],
            ],
        ],
        'inline' => [
            'js' => [
                function (Request $request) {
                    $locale = $request->getAttribute('locale', Customization::DEFAULT_LOCALE);
                    $locale = explode('.', $locale)[0];
                    $localeShort = substr($locale, 0, 2);
                    $localeWithDashes = str_replace('_', '-', $locale);

                    $app = [
                        'lang' => [
                            'confirm' => __('Are you sure?'),
                            'advanced' => __('Advanced'),
                        ],
                        'locale' => $locale,
                        'locale_short' => $localeShort,
                        'locale_with_dashes' => $localeWithDashes,
                    ];

                    return 'let App = ' . json_encode($app) . ';';
                },
            ],
        ],
    ],

    'main' => [
        'order' => 3,
        'require' => ['minimal'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/sweetalert2/sweetalert2.min.js',
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
        ],
    ],
    'theme_light' => [
        'order' => 50,
        'files' => [
            'css' => [
                [
                    'href' => 'dist/light.css',
                ],
            ],
        ],
    ],

    /*
     * Asset collections
     */
    'forms_common' => [
        'require' => ['zxcvbn', 'select2', 'dirrty'],
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
                    'href' => 'dist/lib/bootgrid/jquery.bootgrid.min.css',
                ],
            ],
            'js' => [
                [
                    'src' => 'dist/lib/bootgrid/jquery.bootgrid.updated.js',
                ],
                [
                    'src' => 'dist/bootgrid.js',
                ],
            ],
        ],
    ],

    'chartjs' => [
        'order' => 20,
        'require' => ['moment_timezone'],

        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/chartjs/Chart.min.js',
                    'defer' => true,
                ],
                [
                    'src' => 'dist/lib/chartjs/chartjs-plugin-colorschemes.min.js',
                    'defer' => true,
                ],
            ],
            'css' => [
                [
                    'href' => 'dist/lib/chartjs/Chart.min.css',
                ],
            ],
        ],
    ],

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

    'select2' => [
        'order' => 10,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/select2/select2.full.min.js',
                    'defer' => true,
                ],
            ],
            'css' => [
                [
                    'href' => 'dist/lib/select2/select2.min.css',
                ],
            ],
        ],
    ],

    'moment_base' => [
        'order' => 8,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/moment/moment.min.js',
                ],
            ],
        ],
        'inline' => [
            'js' => [
                function (Request $request) {
                    return 'moment.locale(App.locale_with_dashes);';
                },
            ],
        ],
    ],

    // Moment standalone (with locales)
    'moment' => [
        'order' => 9,
        'require' => ['moment_base'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/moment/locales.min.js',
                    'charset' => 'UTF-8',
                ],
            ],
        ],
    ],

    'moment_timezone' => [
        'order' => 9,
        'require' => ['moment_base'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/moment-timezone/moment-timezone-with-data-10-year-range.min.js',
                ],
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

    'dirrty' => [
        'order' => 10,
        'require' => ['jquery'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/dirrty/jquery.dirrty.js',
                    'defer' => true,
                ],
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
                ],
            ],
        ],
    ],

    'nchan' => [
        'order' => 10,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/nchan/NchanSubscriber.js',
                    'defer' => true,
                ],
            ],
        ],
    ],

    'leaflet' => [
        'order' => 20,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/leaflet/leaflet.js',
                    'defer' => true,
                ],
                [
                    'src' => 'dist/lib/leaflet-fullscreen/Control.FullScreen.js',
                    'defer' => true,
                ],
            ],
            'css' => [
                [
                    'href' => 'dist/lib/leaflet/leaflet.css',
                ],
                [
                    'href' => 'dist/lib/leaflet-fullscreen/Control.FullScreen.css',
                ],
            ],
        ],
    ],

    'Webcaster' => [
        'order' => 10,
        'require' => ['vue-component-common'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/webcaster/libshine.js',
                ],
                [
                    'src' => 'dist/lib/webcaster/libsamplerate.js',
                ],
                [
                    'src' => 'dist/lib/webcaster/taglib.js',
                ],
                [
                    'src' => 'dist/lib/webcaster/webcast.js',
                ],
            ],
        ],
    ],

    'StationMedia' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue'],
        // Auto-managed by Assets
    ],

    'StationPlaylists' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'moment_base', 'moment_timezone'],
        'replace' => ['moment'],
        // Auto-managed by Assets
    ],

    'StationStreamers' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'moment'],
        // Auto-managed by Assets
    ],

    'StationOnDemand' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue'],
        // Auto-managed by Assets
    ],

    'PublicRadioPlayer' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'moment'],
        // Auto-managed by Assets
    ],

    'SongRequest' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue'],
        // Auto-managed by Assets
    ],

    'StationProfile' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'moment'],
        // Auto-managed by Assets
    ],

    'AdminStorageLocations' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue'],
        // Auto-managed by Assets
    ],
];
