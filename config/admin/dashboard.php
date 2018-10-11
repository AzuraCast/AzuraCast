<?php
/**
 * Administrative dashboard configuration.
 */

return [
    __('System Maintenance') => [
        'id' => 'maintenance',
        'icon' => 'router',
        'items' => [
            __('System Settings') => [
                'url' => 'admin:settings:index',
                'icon' => 'settings',
                'permission' => 'administer settings',
            ],
            __('Custom Branding') => [
                'url' => 'admin:branding:index',
                'icon' => 'brush',
                'permission' => 'administer settings',
            ],
            __('API Keys') => [
                'url' => 'admin:api:index',
                'icon' => 'vpn_key',
                'permission' => 'administer api keys',
            ],
        ],
    ],
    __('Users') => [
        'id' => 'users',
        'icon' => 'group',
        'items' => [
            __('User Accounts') => [
                'url' => 'admin:users:index',
                'icon' => 'account_circle',
                'permission' => 'administer user accounts',
            ],
            __('Permissions') => [
                'url' => 'admin:permissions:index',
                'icon' => 'lock',
                'permission' => 'administer permissions',
            ],
        ],
    ],
    __('Stations') => [
        'id' => 'stations',
        'icon' => 'volume_up',
        'items' => [
            __('Manage %s', __('Stations')) => [
                'url' => 'admin:stations:index',
                'icon' => 'speaker_group',
                'permission' => 'administer stations',
            ],
            __('Manage %s', __('Custom Fields')) => [
                'url' => 'admin:custom_fields:index',
                'icon' => 'list',
                'permission' => 'administer custom fields',
            ],
            __('Install SHOUTcast') => [
                'url' => 'admin:install:shoutcast',
                'icon' => 'file_download',
                'permission' => 'administer all',
            ],
        ],
    ],
];
