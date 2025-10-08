import {useTranslate} from "~/vendor/gettext.ts";
import {filterMenu, RawMenuCategory} from "~/functions/filterMenu.ts";
import {GlobalPermissions} from "~/entities/ApiInterfaces.ts";
import IconIcGroup from "~icons/ic/baseline-group";
import IconIcRadio from "~icons/ic/baseline-radio";
import IconIcRouter from "~icons/ic/baseline-router";
import IconIcWidgets from "~icons/ic/baseline-widgets";
import {useUserAllowed} from "~/functions/useUserAllowed.ts";

export function useAdminMenu() {
    const {$gettext} = useTranslate();
    const {userAllowed} = useUserAllowed();

    const fullMenu: RawMenuCategory[] = [
        {
            key: 'maintenance',
            label: $gettext('System Maintenance'),
            icon: () => IconIcRouter,
            items: [
                {
                    key: 'settings',
                    label: $gettext('System Settings'),
                    url: {
                        name: 'admin:settings:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.Settings)
                },
                {
                    key: 'branding',
                    label: $gettext('Custom Branding'),
                    url: {
                        name: 'admin:branding:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.Settings)
                },
                {
                    key: 'logs',
                    label: $gettext('System Logs'),
                    url: {
                        name: 'admin:logs:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.Logs)
                },
                {
                    key: 'storage_locations',
                    label: $gettext('Storage Locations'),
                    url: {
                        name: 'admin:storage_locations:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.StorageLocations)
                },
                {
                    key: 'backups',
                    label: $gettext('Backups'),
                    url: {
                        name: 'admin:backups:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.Backups)
                },
                {
                    key: 'debug',
                    label: $gettext('System Debugger'),
                    url: {
                        name: 'admin:debug:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.All)
                },
                {
                    key: 'updates',
                    label: $gettext('Update AzuraCast'),
                    url: {
                        name: 'admin:updates:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.All)
                }
            ]
        },
        {
            key: 'users',
            label: $gettext('Users'),
            icon: () => IconIcGroup,
            items: [
                {
                    key: 'manage_users',
                    label: $gettext('User Accounts'),
                    url: {
                        name: 'admin:users:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.All)
                },
                {
                    key: 'permissions',
                    label: $gettext('Roles & Permissions'),
                    url: {
                        name: 'admin:permissions:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.All)
                },
                {
                    key: 'auditlog',
                    label: $gettext('Audit Log'),
                    url: {
                        name: 'admin:auditlog:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.Logs)
                },
                {
                    key: 'api_keys',
                    label: $gettext('API Keys'),
                    url: {
                        name: 'admin:api:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.ApiKeys)
                }
            ]
        },
        {
            key: 'stations',
            label: $gettext('Stations'),
            icon: () => IconIcRadio,
            items: [
                {
                    key: 'manage_stations',
                    label: $gettext('Stations'),
                    url: {
                        name: 'admin:stations:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.Stations)
                },
                {
                    key: 'custom_fields',
                    label: $gettext('Custom Fields'),
                    url: {
                        name: 'admin:custom_fields:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.CustomFields)
                },
                {
                    key: 'relays',
                    label: $gettext('Connected AzuraRelays'),
                    url: {
                        name: 'admin:relays:index',
                    },
                    visible: () => userAllowed(GlobalPermissions.Stations)
                },
            ],
        },
        {
            key: 'software',
            label: $gettext('Third-Party Software'),
            icon: () => IconIcWidgets,
            items: [
                {
                    key: 'shoutcast',
                    label: $gettext('Shoutcast 2 DNAS'),
                    url: {
                        name: 'admin:install_shoutcast:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.Settings)
                },
                {
                    key: 'rsas',
                    label: $gettext('Rocket Streaming Audio Server (RSAS)'),
                    url: {
                        name: 'admin:install_rsas:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.Settings)
                },
                {
                    key: 'stereo_tool',
                    label: $gettext('Stereo Tool'),
                    url: {
                        name: 'admin:stereo_tool:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.Settings)
                },
                {
                    key: 'geolite',
                    label: $gettext('MaxMind GeoLite IP Database'),
                    url: {
                        name: 'admin:install_geolite:index'
                    },
                    visible: () => userAllowed(GlobalPermissions.Settings)
                }
            ]
        }
    ];

    return filterMenu(fullMenu);
}
