<?php
/**
 * Administrative dashboard configuration.
 */

use App\Acl;

return [
    __('System Maintenance') => [
        'id' => 'maintenance',
        'icon' => 'router',
        'items' => [
            __('System Settings') => [
                'url' => 'admin:settings:index',
                'permission' => Acl::GLOBAL_SETTINGS,
            ],
            __('Custom Branding') => [
                'url' => 'admin:branding:index',
                'permission' => Acl::GLOBAL_SETTINGS,
            ],
            __('API Keys') => [
                'url' => 'admin:api:index',
                'permission' => Acl::GLOBAL_API_KEYS,
            ],
            __('System Logs') => [
                'url' => 'admin:logs:index',
                'permission' => Acl::GLOBAL_LOGS,
            ],
        ],
    ],
    __('Users') => [
        'id' => 'users',
        'icon' => 'group',
        'items' => [
            __('User Accounts') => [
                'url' => 'admin:users:index',
                'permission' => Acl::GLOBAL_USERS,
            ],
            __('Permissions') => [
                'url' => 'admin:permissions:index',
                'permission' => Acl::GLOBAL_PERMISSIONS,
            ],
        ],
    ],
    __('Stations') => [
        'id' => 'stations',
        'icon' => 'volume_up',
        'items' => [
            __('Manage %s', __('Stations')) => [
                'url' => 'admin:stations:index',
                'permission' => Acl::GLOBAL_STATIONS,
            ],
            __('Manage %s', __('Custom Fields')) => [
                'url' => 'admin:custom_fields:index',
                'permission' => Acl::GLOBAL_CUSTOM_FIELDS,
            ],
            __('Install SHOUTcast') => [
                'url' => 'admin:install:shoutcast',
                'permission' => Acl::GLOBAL_ALL,
            ],
        ],
    ],
];
