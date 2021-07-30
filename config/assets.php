<?php

use App\Environment;
use App\Http\ServerRequest;
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
                    'src' => 'dist/lib/vue/' . (Environment::getInstance()->isProduction() ? 'vue.min.js' : 'vue.js'),
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
                    'href' => 'dist/style.css',
                ],
            ],
        ],
        'inline' => [
            'js' => [
                function (Request $request) {
                    /** @var App\Session\Flash|null $flashObj */
                    $flashObj = $request->getAttribute(ServerRequest::ATTR_SESSION_FLASH);

                    if (null === $flashObj || !$flashObj->hasMessages()) {
                        return null;
                    }

                    $notifies = [];
                    foreach ($flashObj->getMessages() as $message) {
                        $notifyMessage = str_replace(['"', "\n"], ['\'', '<br>'], $message['text']);
                        $notifies[] = 'notify("' . $notifyMessage . '", "' . $message['color'] . '");';
                    }

                    return '$(function () { ' . implode('', $notifies) . ' });';
                },
                function (Request $request) {
                    /** @var App\Locale|null $locale */
                    $localeObj = $request->getAttribute(ServerRequest::ATTR_LOCALE);

                    $locale = ($localeObj instanceof App\Locale)
                        ? (string)$localeObj
                        : App\Locale::DEFAULT_LOCALE;

                    $locale = explode('.', $locale, 2)[0];
                    $localeShort = substr($locale, 0, 2);
                    $localeWithDashes = str_replace('_', '-', $locale);

                    $app = [
                        'lang' => [
                            'confirm' => __('Are you sure?'),
                            'advanced' => __('Advanced'),
                            'pw_blank' => __('Enter a password to continue.'),
                            'pw_good' => __('No problems detected.'),
                        ],
                        'locale' => $locale,
                        'locale_short' => $localeShort,
                        'locale_with_dashes' => $localeWithDashes,
                    ];

                    return 'let App = ' . json_encode($app) . ';';
                },
                <<<'JS'
                    let currentTheme = document.documentElement.getAttribute('data-theme');
                    if (currentTheme === 'browser') {
                        currentTheme = (window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                    }
                    App.theme = currentTheme;
                JS,
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
                [
                    'src' => 'dist/lib/chartjs/hammer.min.js',
                    'defer' => true,
                ],
                [
                    'src' => 'dist/lib/chartjs/chartjs-plugin-zoom.min.js',
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

    // Moment standalone (with locales)
    'moment' => [
        'order' => 8,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/moment/moment.min.js',
                ],
                [
                    'src' => 'dist/lib/moment/locales.min.js',
                    'charset' => 'UTF-8',
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

    'moment_timezone' => [
        'order' => 9,
        'require' => ['moment'],
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

    'codemirror' => [
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
                [
                    'src' => 'dist/lib/codemirror/javascript.js',
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

    'Vue_Dashboard' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'chartjs'],
        // Auto-managed by Assets
    ],

    'Vue_PublicFullPlayer' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'moment', 'fancybox'],
        // Auto-managed by Assets
    ],

    'Vue_PublicHistory' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'moment'],
        // Auto-managed by Assets
    ],

    'Vue_AdminBranding' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'fancybox', 'codemirror'],
        // Auto-managed by Assets
    ],

    'Vue_AdminStorageLocations' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue'],
        // Auto-managed by Assets
    ],

    'Vue_PublicOnDemand' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue'],
        // Auto-managed by Assets
    ],

    'Vue_PublicRequests' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue'],
        // Auto-managed by Assets
    ],

    'Vue_PublicSchedule' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'moment_timezone'],
        // Auto-managed by Assets
    ],

    'Vue_PublicWebDJ' => [
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

    'Vue_StationsMedia' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'fancybox'],
        // Auto-managed by Assets
    ],

    'Vue_StationsMounts' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue'],
        // Auto-managed by Assets
    ],

    'Vue_StationsPlaylists' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'moment_timezone'],
        // Auto-managed by Assets
    ],

    'Vue_StationsPodcasts' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'fancybox', 'moment_timezone'],
        // Auto-managed by Assets
    ],

    'Vue_StationsPodcastEpisodes' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'fancybox', 'moment_timezone'],
        // Auto-managed by Assets
    ],

    'Vue_StationsProfile' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'moment', 'fancybox'],
        // Auto-managed by Assets
    ],

    'Vue_StationsQueue' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'moment'],
        // Auto-managed by Assets
    ],

    'Vue_StationsStreamers' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'moment'],
        // Auto-managed by Assets
    ],

    'Vue_StationsReportsOverview' => [
        'order' => 10,
        'require' => ['vue-component-common', 'bootstrap-vue', 'chartjs'],
        // Auto-managed by Assets
    ],
];
