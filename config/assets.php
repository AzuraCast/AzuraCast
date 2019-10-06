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
        'order' => 2,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/vue_gettext.js',
                ],
            ],
        ],
        'inline' => [
            'js' => [
                function (Request $request) {
                    $locale = $request->getAttribute('locale', Customization::DEFAULT_LOCALE);
                    $locale = substr($locale, 0, 5);
                    return 'VueTranslations.default(' . json_encode($locale) . ');';
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
        'require' => ['zxcvbn', 'chosen', 'dirrty'],
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
                    $locale = $request->getAttribute('locale', Customization::DEFAULT_LOCALE);
                    $locale = str_replace('_', '-', explode('.', $locale)[0]);
                    return 'moment.locale(' . json_encode($locale) . ');';
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
        'require' => ['moment_base', 'moment_timezone'],
        'replace' => ['moment'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/fullcalendar/fullcalendar.min.js',
                ],
                [
                    'src' => 'dist/lib/fullcalendar/locale-all.js',
                ],
            ],
            'css' => [
                [
                    'href' => 'dist/lib/fullcalendar/fullcalendar.min.css',
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

    'sortable' => [
        'order' => 10,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/sortable/Sortable.min.js',
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
            ],
            'css' => [
                [
                    'href' => 'dist/lib/leaflet/leaflet.css',
                ],
            ],
        ],
    ],

    'webcaster' => [
        'order' => 10,
        'require' => ['vue', 'vue-translations'],
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
                [
                    'src' => 'dist/webcaster.js',
                ],
            ],
        ],
    ],

    'radio_player' => [
        'order' => 10,
        'require' => ['vue', 'vue-translations'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/radio_player.js',
                ],
            ],
        ],
    ],

    'inline_player' => [
        'order' => 10,
        'require' => ['vue', 'vue-translations'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/inline_player.js',
                ],
            ],
        ],
    ],

    'station_media_manager' => [
        'order' => 10,
        'require' => ['vue', 'vue-translations', 'bootstrap-vue'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/station_media.js',
                ],
            ],
        ],
    ],
];
