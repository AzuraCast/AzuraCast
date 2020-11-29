<?php
/**
 * Administrative dashboard configuration.
 */

use App\Acl;

return function (App\Event\BuildAdminMenu $e) {
    $router = $e->getRouter();

    $e->merge([
        'maintenance' => [
            'label' => __('System Maintenance'),
            'icon' => 'router',
            'items' => [
                'settings' => [
                    'label' => __('System Settings'),
                    'url' => $router->named('admin:settings:index'),
                    'permission' => Acl::GLOBAL_SETTINGS,
                ],
                'branding' => [
                    'label' => __('Custom Branding'),
                    'url' => $router->named('admin:branding:index'),
                    'permission' => Acl::GLOBAL_SETTINGS,
                ],
                'logs' => [
                    'label' => __('System Logs'),
                    'url' => $router->named('admin:logs:index'),
                    'permission' => Acl::GLOBAL_LOGS,
                ],
                'storage_locations' => [
                    'label' => __('Storage Locations'),
                    'url' => $router->named('admin:storage_locations:index'),
                    'permission' => Acl::GLOBAL_STORAGE_LOCATIONS,
                ],
                'backups' => [
                    'label' => __('Backups'),
                    'url' => $router->named('admin:backups:index'),
                    'permission' => Acl::GLOBAL_BACKUPS,
                ],
                'debug' => [
                    'label' => __('System Debugger'),
                    'url' => $router->named('admin:debug:index'),
                    'permission' => Acl::GLOBAL_ALL,
                ],
            ],
        ],
        'users' => [
            'label' => __('Users'),
            'icon' => 'group',
            'items' => [
                'manage_users' => [
                    'label' => __('User Accounts'),
                    'url' => $router->named('admin:users:index'),
                    'permission' => Acl::GLOBAL_ALL,
                ],
                'permissions' => [
                    'label' => __('Permissions'),
                    'url' => $router->named('admin:permissions:index'),
                    'permission' => Acl::GLOBAL_ALL,
                ],
                'auditlog' => [
                    'label' => __('Audit Log'),
                    'url' => $router->named('admin:auditlog:index'),
                    'permission' => Acl::GLOBAL_LOGS,
                ],
                'api_keys' => [
                    'label' => __('API Keys'),
                    'url' => $router->named('admin:api:index'),
                    'permission' => Acl::GLOBAL_API_KEYS,
                ],
            ],
        ],
        'stations' => [
            'label' => __('Stations'),
            'icon' => 'volume_up',
            'items' => [
                'manage_stations' => [
                    'label' => __('Stations'),
                    'url' => $router->named('admin:stations:index'),
                    'permission' => Acl::GLOBAL_STATIONS,
                ],
                'custom_fields' => [
                    'label' => __('Custom Fields'),
                    'url' => $router->named('admin:custom_fields:index'),
                    'permission' => Acl::GLOBAL_CUSTOM_FIELDS,
                ],
                'relays' => [
                    'label' => __('Connected AzuraRelays'),
                    'url' => $router->named('admin:relays:index'),
                    'permission' => Acl::GLOBAL_STATIONS,
                ],
                'shoutcast' => [
                    'label' => __('Install SHOUTcast'),
                    'url' => $router->named('admin:install_shoutcast:index'),
                    'permission' => Acl::GLOBAL_ALL,
                ],
                'geolite' => [
                    'label' => __('Install GeoLite IP Database'),
                    'url' => $router->named('admin:install_geolite:index'),
                    'permission' => Acl::GLOBAL_ALL,
                ],
            ],
        ],
    ]);
};
