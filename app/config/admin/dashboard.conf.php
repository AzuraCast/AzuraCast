<?php
/**
 * Administrative dashboard configuration.
 */

return [
    _('System Maintenance') => [
        'icon' => 'zmdi zmdi-router',
        'items' => [
            _('System Settings') => [
                'url' => 'admin:settings:index',
                'icon' => 'zmdi zmdi-settings',
                'permission' => 'administer settings',
            ],
            _('Custom Branding') => [
                'url' => 'admin:branding:index',
                'icon' => 'zmdi zmdi-brush',
                'permission' => 'administer settings',
            ],
            _('API Keys') => [
                'url' => 'admin:api:index',
                'icon' => 'zmdi zmdi-key',
                'permission' => 'administer api keys',
            ],
        ],
    ],
    _('Users') => [
        'icon' => 'zmdi zmdi-accounts',
        'items' => [
            _('User Accounts') => [
                'url' => 'admin:users:index',
                'icon' => 'zmdi zmdi-account',
                'permission' => 'administer user accounts',
            ],
            _('Permissions') => [
                'url' => 'admin:permissions:index',
                'icon' => 'zmdi zmdi-lock',
                'permission' => 'administer permissions',
            ],
        ],
    ],
    _('Stations') => [
        'icon' => 'zmdi zmdi-volume-up',
        'items' => [
            _('Manage Stations') => [
                'url' => 'admin:stations:index',
                'icon' => 'zmdi zmdi-surround-sound',
                'permission' => 'administer stations',
            ],
        ],
    ],
];