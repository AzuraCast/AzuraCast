<?php
/**
 * Administrative dashboard configuration.
 */

return [
    _('System Maintenance') => [
        _('System Settings') => [
            'url' => 'admin:settings:index',
            'icon' => 'fa fa-cog',
            'permission' => 'administer all',
        ],
        _('API Keys') => [
            'url' => 'admin:api:index',
            'icon' => 'fa fa-share-alt',
            'permission' => 'administer api keys',
        ],
    ],
    _('Users') => [
        _('User Accounts') => [
            'url' => 'admin:users:index',
            'icon' => 'fa fa-user',
            'permission' => 'administer all',
        ],
        _('Roles and Permissions') => [
            'url' => 'admin:permissions:index',
            'icon' => 'fa fa-key',
            'permission' => 'administer all',
        ],
    ],
    _('Stations') => [
        _('Manage Stations') => [
            'url' => 'admin:stations:index',
            'icon' => 'fa fa-cog',
            'permission' => 'administer stations',
        ],
    ],
];