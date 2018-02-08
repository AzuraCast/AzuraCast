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
            'light' => _('Light').' ('._('Default').')',
            'dark' => _('Dark'),
        ],
    ],

    'actions' => [
        'global' => [
            'administer all' => _('All Permissions'),
            'view administration' => _('View Administration Page'),
            'administer settings' => _('Administer Settings'),
            'administer api keys' => _('Administer API Keys'),
            'administer user accounts' => _('Administer User Accounts'),
            'administer permissions' => _('Administer Permissions'),
            'administer stations' => _('Administer Stations'),
        ],
        'station' => [
            'administer all' => _('All Permissions'),
            'view station management' => _('View Station Page'),
            'view station reports' => _('View Station Reports'),
            'manage station profile' => _('Manage Station Profile'),
            'manage station broadcasting' => _('Manage Station Broadcasting'),
            'manage station streamers' => _('Manage Station Streamers'),
            'manage station mounts' => _('Manage Station Mount Points'),
            'manage station media' => _('Manage Station Media'),
            'manage station automation' => _('Manage Station Automation'),
        ],
    ],

];