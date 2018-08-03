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
            'it_IT.UTF-8' => 'Italiano',            // Italian
            'hu_HU.UTF-8' => 'magyar',              // Hungarian
            'nl_NL.UTF-8' => 'Nederlands',          // Dutch
            'pl_PL.UTF-8' => 'Polski',              // Polish
            'pt_PT.UTF-8' => 'Português',           // Portuguese
            'pt_BR.UTF-8' => 'Português do Brasil', // Brazilian Portuguese
            'ru_RU.UTF-8' => 'Русский язык',        // Russian
            // 'sv_SE.UTF-8' => 'Svenska',          // Swedish
        ],
    ],

    // PHP date() formats for locales available above.
    'time_formats' => [
        'default' => 'G:i',
        'en_US.UTF-8' => 'g:i A',
    ],

    'themes' => [
        'default' => 'light',
        'available' => ['light', 'dark'],
    ],

];