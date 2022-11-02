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
                    $localeObj = $request->getAttribute(ServerRequest::ATTR_LOCALE);

                    $locale = ($localeObj instanceof App\Enums\SupportedLocales)
                        ? $localeObj->value
                        : App\Enums\SupportedLocales::default()->value;

                    $locale = explode('.', $locale, 2)[0];
                    $localeShort = substr($locale, 0, 2);
                    $localeWithDashes = str_replace('_', '-', $locale);

                    // User profile-specific 24-hour display setting.
                    $userObj = $request->getAttribute(ServerRequest::ATTR_USER);
                    $show24Hours = ($userObj instanceof App\Entity\User)
                        ? $userObj->getShow24HourTime()
                        : null;

                    $timeConfig = new \stdClass();
                    if (null !== $show24Hours) {
                        $timeConfig->hour12 = !$show24Hours;
                    }

                    $app = [
                        'lang' => [
                            'confirm' => __('Are you sure?'),
                            'advanced' => __('Advanced'),
                        ],
                        'locale' => $locale,
                        'locale_short' => $localeShort,
                        'locale_with_dashes' => $localeWithDashes,
                        'time_config' => $timeConfig,
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

    'luxon' => [
        'order' => 8,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/luxon/luxon.min.js',
                ],
            ],
        ],
        'inline' => [
            'js' => [
                function (Request $request) {
                    return <<<'JS'
                    luxon.Settings.defaultLocale = App.locale_with_dashes;
                    luxon.Settings.defaultZoneName = 'UTC';
                    JS;
                },
            ],
        ],
    ],

    'humanize-duration' => [
        'order' => 8,
        'files' => [
            'js' => [
                [
                    'src' => 'dist/lib/humanize-duration/humanize-duration.js',
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

    'Vue_PublicWebDJ' => [
        'order' => 10,
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
];
