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
            'administer settings' => sprintf(_('Administer %s'), _('Settings')),
            'administer api keys' => sprintf(_('Administer %s'), _('API Keys')),
            'administer user accounts' => sprintf(_('Administer %s'), _('Users')),
            'administer permissions' => sprintf(_('Administer %s'), _('Permissions')),
            'administer stations' => sprintf(_('Administer %s'), _('Stations')),
        ],
        'station' => [
            'administer all' => _('All Permissions'),
            'view station management' => _('View Station Page'),
            'view station reports' => _('View Station Reports'),
            'manage station profile' => sprintf(_('Manage Station %s'), _('Profile')),
            'manage station broadcasting' => sprintf(_('Manage Station %s'), _('Broadcasting')),
            'manage station streamers' => sprintf(_('Manage Station %s'), _('Streamers')),
            'manage station mounts' => sprintf(_('Manage Station %s'), _('Mount Points')),
            'manage station media' => sprintf(_('Manage Station %s'), _('Media')),
            'manage station automation' => sprintf(_('Manage Station %s'), _('Automation')),
            'manage station web hooks' => sprintf(_('Manage Station %s'), _('Web Hooks')),
        ],
    ],

];