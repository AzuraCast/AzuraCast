import {useTranslate} from "~/vendor/gettext.ts";
import {GlobalPermission, userAllowed} from "~/acl.ts";
import filterMenu from "~/functions/filterMenu.ts";

export function useAdminMenu(): array {
    const {$gettext} = useTranslate();

    const menu = [
        {
            key: 'maintenance',
            label: $gettext('System Maintenance'),
            icon: 'router',
            items: [
                {
                    key: 'settings',
                    label: $gettext('System Settings'),
                    url: {
                        name: 'admin:settings:index'
                    },
                    visible: userAllowed(GlobalPermission.Settings)
                },
                {
                    key: 'branding',
                    label: $gettext('Custom Branding'),
                    url: {
                        name: 'admin:branding:index'
                    },
                    visible: userAllowed(GlobalPermission.Settings)
                },
                {
                    key: 'logs',
                    label: $gettext('System Logs'),
                    url: {
                        name: 'admin:logs:index'
                    },
                    visible: userAllowed(GlobalPermission.Logs)
                },
                {
                    key: 'storage_locations',
                    label: $gettext('Storage Locations'),
                    url: {
                        name: 'admin:storage_locations:index'
                    },
                    visible: userAllowed(GlobalPermission.StorageLocations)
                },
                {
                    key: 'backups',
                    label: $gettext('Backups'),
                    url: {
                        name: 'admin:backups:index'
                    },
                    visible: userAllowed(GlobalPermission.Backups)
                },
                {
                    key: 'debug',
                    label: $gettext('System Debugger'),
                    url: {
                        name: 'admin:debug:index'
                    },
                    visible: userAllowed(GlobalPermission.All)
                },
                {
                    key: 'updates',
                    label: $gettext('Update AzuraCast'),
                    url: {
                        name: 'admin:updates:index'
                    },
                    visible: userAllowed(GlobalPermission.All)
                }
            ]
        },
        {
            key: 'users',
            label: $gettext('Users'),
            icon: 'group',
            items: [
                {
                    key: 'manage_users',
                    label: $gettext('User Accounts'),
                    url: {
                        name: 'admin:users:index'
                    },
                    visible: userAllowed(GlobalPermission.All)
                },
                {
                    key: 'permissions',
                    label: $gettext('Roles & Permissions'),
                    url: {
                        name: 'admin:permissions:index'
                    },
                    visible: userAllowed(GlobalPermission.All)
                },
                {
                    key: 'auditlog',
                    label: $gettext('Audit Log'),
                    url: {
                        name: 'admin:auditlog:index'
                    },
                    visible: userAllowed(GlobalPermission.Logs)
                },
                {
                    key: 'api_keys',
                    label: $gettext('API Keys'),
                    url: {
                        name: 'admin:api:index'
                    },
                    visible: userAllowed(GlobalPermission.ApiKeys)
                }
            ]
        },
        {
            key: 'stations',
            label: $gettext('Stations'),
            icon: 'volume_up',
            items: [
                {
                    key: 'manage_stations',
                    label: $gettext('Stations'),
                    url: {
                        name: 'admin:stations:index'
                    },
                    visible: userAllowed(GlobalPermission.Stations)
                },
                {
                    key: 'custom_fields',
                    label: $gettext('Custom Fields'),
                    url: {
                        name: 'admin:custom_fields:index'
                    },
                    visible: userAllowed(GlobalPermission.CustomFields)
                },
                {
                    key: 'relays',
                    label: $gettext('Connected AzuraRelays'),
                    url: {
                        name: 'admin:relays:index',
                    },
                    visible: userAllowed(GlobalPermission.Stations)
                },
                {
                    key: 'shoutcast',
                    label: $gettext('Install Shoutcast'),
                    url: {
                        name: 'admin:install_shoutcast:index'
                    },
                    visible: userAllowed(GlobalPermission.All)
                },
                {
                    key: 'stereo_tool',
                    label: $gettext('Install Stereo Tool'),
                    url: {
                        name: 'admin:stereo_tool:index'
                    },
                    visible: userAllowed(GlobalPermission.All)
                },
                {
                    key: 'geolite',
                    label: $gettext('Install GeoLite IP Database'),
                    url: {
                        name: 'admin:install_geolite:index'
                    },
                    visible: userAllowed(GlobalPermission.All)
                }
            ]
        }
    ];

    return filterMenu(menu);
}
