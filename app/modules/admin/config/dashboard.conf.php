<?php
/**
 * Administrative dashboard configuration.
 */

return [
    'System Maintenance' => [
        'System Settings' => [
            'url' => 'admin:settings:index',
            'icon' => 'fa fa-cog',
            'permission' => 'administer all',
        ],
        'API Keys' => [
            'url' => 'admin:api:index',
            'icon' => 'fa fa-share-alt',
            'permission' => 'administer api keys',
        ],
    ],
    'Users' => [
        'User Accounts' => [
            'url' => 'admin:users:index',
            'icon' => 'fa fa-user',
            'permission' => 'administer all',
        ],
        'Roles and Permissions' => [
            'url' => 'admin:permissions:index',
            'icon' => 'fa fa-key',
            'permission' => 'administer all',
        ],
    ],
    'Stations' => [
        'Manage Stations' => [
            'url' => 'admin:stations:index',
            'icon' => 'fa fa-cog',
            'permission' => 'administer stations',
        ],
    ],
];