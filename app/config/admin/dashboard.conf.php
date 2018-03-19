<?php
/**
 * Administrative dashboard configuration.
 */

return [
    __('System Maintenance') => [
        'icon' => 'zmdi zmdi-router',
        'items' => [
            __('System Settings') => [
                'url' => 'admin:settings:index',
                'icon' => 'zmdi zmdi-settings',
                'permission' => 'administer settings',
            ],
            __('Custom Branding') => [
                'url' => 'admin:branding:index',
                'icon' => 'zmdi zmdi-brush',
                'permission' => 'administer settings',
            ],
            __('API Keys') => [
                'url' => 'admin:api:index',
                'icon' => 'zmdi zmdi-key',
                'permission' => 'administer api keys',
            ],
        ],
    ],
    __('Users') => [
        'icon' => 'zmdi zmdi-accounts',
        'items' => [
            __('User Accounts') => [
                'url' => 'admin:users:index',
                'icon' => 'zmdi zmdi-account',
                'permission' => 'administer user accounts',
            ],
            __('Permissions') => [
                'url' => 'admin:permissions:index',
                'icon' => 'zmdi zmdi-lock',
                'permission' => 'administer permissions',
            ],
        ],
    ],
    __('Stations') => [
        'icon' => 'zmdi zmdi-volume-up',
        'items' => [
            __('Manage Stations') => [
                'url' => 'admin:stations:index',
                'icon' => 'zmdi zmdi-surround-sound',
                'permission' => 'administer stations',
            ],
        ],
    ],
];