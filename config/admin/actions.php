<?php
use App\Acl;

return [
    'global' => [
        Acl::GLOBAL_ALL             => __('All Permissions'),
        Acl::GLOBAL_VIEW            => __('View Administration Page'),
        Acl::GLOBAL_LOGS            => __('View System Logs'),
        Acl::GLOBAL_SETTINGS        => sprintf(__('Administer %s'), __('Settings')),
        Acl::GLOBAL_API_KEYS        => sprintf(__('Administer %s'), __('API Keys')),
        Acl::GLOBAL_USERS           => sprintf(__('Administer %s'), __('Users')),
        Acl::GLOBAL_PERMISSIONS     => sprintf(__('Administer %s'), __('Permissions')),
        Acl::GLOBAL_STATIONS        => sprintf(__('Administer %s'), __('Stations')),
        Acl::GLOBAL_CUSTOM_FIELDS   => sprintf(__('Administer %s'), __('Custom Fields')),
    ],
    'station' => [
        Acl::STATION_ALL            => __('All Permissions'),
        Acl::STATION_VIEW           => __('View Station Page'),
        Acl::STATION_REPORTS        => __('View Station Reports'),
        Acl::STATION_LOGS           => __('View Station Logs'),
        Acl::STATION_PROFILE        => sprintf(__('Manage Station %s'), __('Profile')),
        Acl::STATION_BROADCASTING   => sprintf(__('Manage Station %s'), __('Broadcasting')),
        Acl::STATION_STREAMERS      => sprintf(__('Manage Station %s'), __('Streamers')),
        Acl::STATION_MOUNTS         => sprintf(__('Manage Station %s'), __('Mount Points')),
        Acl::STATION_REMOTES        => sprintf(__('Manage Station %s'), __('Remote Relays')),
        Acl::STATION_MEDIA          => sprintf(__('Manage Station %s'), __('Media')),
        Acl::STATION_AUTOMATION     => sprintf(__('Manage Station %s'), __('Automation')),
        Acl::STATION_WEB_HOOKS      => sprintf(__('Manage Station %s'), __('Web Hooks')),
    ],
];
