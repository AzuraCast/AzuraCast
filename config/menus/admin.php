<?php
/**
 * Administrative dashboard configuration.
 */

use App\Enums\GlobalPermissions;

return function (App\Event\BuildAdminMenu $e) {
    $request = $e->getRequest();
    $router = $request->getRouter();

    $e->merge(
        [
            'maintenance' => [
                'label' => __('System Maintenance'),
                'icon'  => 'router',
                'items' => [
                    'settings'          => [
                        'label'      => __('System Settings'),
                        'url'        => (string)$router->named('admin:settings:index'),
                        'permission' => GlobalPermissions::Settings,
                    ],
                    'branding'          => [
                        'label'      => __('Custom Branding'),
                        'url'        => (string)$router->named('admin:branding:index'),
                        'permission' => GlobalPermissions::Settings,
                    ],
                    'logs'              => [
                        'label'      => __('System Logs'),
                        'url'        => (string)$router->named('admin:logs:index'),
                        'permission' => GlobalPermissions::Logs,
                    ],
                    'storage_locations' => [
                        'label'      => __('Storage Locations'),
                        'url'        => (string)$router->named('admin:storage_locations:index'),
                        'permission' => GlobalPermissions::StorageLocations,
                    ],
                    'backups'           => [
                        'label'      => __('Backups'),
                        'url'        => (string)$router->named('admin:backups:index'),
                        'permission' => GlobalPermissions::Backups,
                    ],
                    'debug'             => [
                        'label'      => __('System Debugger'),
                        'url'        => (string)$router->named('admin:debug:index'),
                        'permission' => GlobalPermissions::All,
                    ],
                ],
            ],
            'users'       => [
                'label' => __('Users'),
                'icon'  => 'group',
                'items' => [
                    'manage_users' => [
                        'label'      => __('User Accounts'),
                        'url'        => (string)$router->named('admin:users:index'),
                        'permission' => GlobalPermissions::All,
                    ],
                    'permissions'  => [
                        'label'      => __('Roles & Permissions'),
                        'url'        => (string)$router->named('admin:permissions:index'),
                        'permission' => GlobalPermissions::All,
                    ],
                    'auditlog'     => [
                        'label'      => __('Audit Log'),
                        'url'        => (string)$router->named('admin:auditlog:index'),
                        'permission' => GlobalPermissions::Logs,
                    ],
                    'api_keys'     => [
                        'label'      => __('API Keys'),
                        'url'        => (string)$router->named('admin:api:index'),
                        'permission' => GlobalPermissions::ApiKeys,
                    ],
                ],
            ],
            'stations'    => [
                'label' => __('Stations'),
                'icon'  => 'volume_up',
                'items' => [
                    'manage_stations' => [
                        'label'      => __('Stations'),
                        'url'        => (string)$router->named('admin:stations:index'),
                        'permission' => GlobalPermissions::Stations,
                    ],
                    'custom_fields'   => [
                        'label'      => __('Custom Fields'),
                        'url'        => (string)$router->named('admin:custom_fields:index'),
                        'permission' => GlobalPermissions::CustomFields,
                    ],
                    'relays' => [
                        'label' => __('Connected AzuraRelays'),
                        'url' => (string)$router->named('admin:relays:index'),
                        'permission' => GlobalPermissions::Stations,
                    ],
                    'shoutcast' => [
                        'label' => __('Install Shoutcast'),
                        'url' => (string)$router->named('admin:install_shoutcast:index'),
                        'permission' => GlobalPermissions::All,
                    ],
                    'stereo_tool' => [
                        'label' => __('Install Stereo Tool'),
                        'url' => (string)$router->named('admin:install_stereo_tool:index'),
                        'permission' => GlobalPermissions::All,
                    ],
                    'geolite' => [
                        'label' => __('Install GeoLite IP Database'),
                        'url' => (string)$router->named('admin:install_geolite:index'),
                        'permission' => GlobalPermissions::All,
                    ],
                ],
            ],
        ]
    );
};
