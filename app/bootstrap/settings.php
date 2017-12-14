<?php
/**
 * Application Settings
 */

return [
    // Application name
    'name' => 'AzuraCast',

    // Subfolder for the application (if applicable)
    'base_uri' => '/',

    // Base of the static URL.
    'static_uri' => '/static/',

    /* Localization Settings */
    'locale' => [
        'default' => 'en_US.UTF-8',
        'supported' => [
            'en_US.UTF-8' => 'English (Default)',
            'de_DE.UTF-8' => 'Deutsch',             // German
            'es_ES.UTF-8' => 'Español',             // Spanish
            'fr_FR.UTF-8' => 'Français',            // French
            'hu_HU.UTF-8' => 'magyar',              // Hungarian
            'pl_PL.UTF-8' => 'Polski',              // Polish
            'ru_RU.UTF-8' => 'Русский язык',        // Russian
            // 'pt_PT.UTF-8' => 'Português',        // Portuguese
            // 'sv_SE.UTF-8' => 'Svenska',          // Swedish
        ],
    ],

    // strftime formats for locales available above.
    'time_formats' => [
        'default' => '%H:%M',
        'en_US.UTF-8' => '%l:%M %p',
    ],

    'themes' => [
        'default' => 'light',
        'available' => [
            'light' => 'Light (Default)',
            'dark' => 'Dark',
        ],
    ],

    'actions' => [
        'global' => [
            'administer all',
            'view administration',
            'administer settings',
            'administer api keys',
            'administer user accounts',
            'administer permissions',
            'administer stations',
        ],
        'station' => [
            'administer all',
            'view station management',
            'view station reports',
            'manage station profile',
            'manage station broadcasting',
            'manage station streamers',
            'manage station mounts',
            'manage station media',
            'manage station automation',
        ],
    ],

];