<?php

use App\Http\ServerRequest;
use App\Middleware\Auth\ApiAuth;
use App\Session\Csrf;
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
                        'api_csrf' => null,
                    ];

                    return 'let App = ' . json_encode($app, JSON_THROW_ON_ERROR) . ';';
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
            ],
        ],
        'inline' => [
            'js' => [
                function (Request $request) {
                    $csrfJson = 'null';
                    $csrf = $request->getAttribute(ServerRequest::ATTR_SESSION_CSRF);
                    if ($csrf instanceof Csrf) {
                        $csrfToken = $csrf->generate(ApiAuth::API_CSRF_NAMESPACE);
                        $csrfJson = json_encode($csrfToken, JSON_THROW_ON_ERROR);
                    }
                    return "App.api_csrf = ${csrfJson};";
                },
            ],
        ],
    ],

    /*
     * Asset collections
     */
    'forms_common' => [
        'require' => ['zxcvbn', 'select2', 'dirrty'],
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/autosize/autosize.min.js',
                    'defer' => true,
                ],
            ],
        ],
    ],

    /*
     * Individual libraries
     */
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
                [
                    'src' => 'dist/lib/moment-timezone/moment-timezone-with-data-10-year-range.min.js',
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

    'Vue_PublicSchedule' => [
        'order' => 10,
        'require' => ['moment'],
        // Auto-managed by Assets
    ],

    'Vue_StationsMedia' => [
        'order' => 10,
        'require' => ['moment'],
        // Auto-managed by Assets
    ],

    'Vue_StationsPlaylists' => [
        'order' => 10,
        'require' => ['moment'],
        // Auto-managed by Assets
    ],

    'Vue_StationsPodcasts' => [
        'order' => 10,
        'require' => ['moment'],
        // Auto-managed by Assets
    ],

    'Vue_StationsStreamers' => [
        'order' => 10,
        'require' => ['moment'],
        // Auto-managed by Assets
    ],
];
