<?php
/**
 * Administrative dashboard configuration.
 */

return [
    _('System Maintenance') => [
        'icon' => 'fa fa-database',
        'items' => [
            _('System Settings') => [
                'url' => 'admin:settings:index',
                'icon' => 'fa fa-cog',
                'permission' => 'administer settings',
            ],
            _('API Keys') => [
                'url' => 'admin:api:index',
                'icon' => 'fa fa-share-alt',
                'permission' => 'administer api keys',
            ],
        ],
    ],
    _('Users') => [
        'icon' => 'fa fa-group',
        'items' => [
            _('User Accounts') => [
                'url' => 'admin:users:index',
                'icon' => 'fa fa-user',
                'permission' => 'administer user accounts',
            ],
            _('Permissions') => [
                'url' => 'admin:permissions:index',
                'icon' => 'fa fa-key',
                'permission' => 'administer permissions',
            ],
        ],
    ],
    _('Stations') => [
        'icon' => 'fa fa-volume-up',
        'items' => [
            _('Manage Stations') => [
                'url' => 'admin:stations:index',
                'icon' => 'fa fa-gears',
                'permission' => 'administer stations',
            ],
        ],
    ],
];