import {useTranslate} from "~/vendor/gettext.ts";
import {GlobalPermission, userAllowed} from "~/acl.ts";
import filterMenu, { MenuCategory, ReactiveMenu } from "~/functions/filterMenu.ts";
import {computed, reactive} from "vue";
import {IconGroups, IconRadio, IconRouter} from "~/components/Common/icons.ts";

export function useAdminMenu(): ReactiveMenu {
    const {$gettext} = useTranslate();

    const menu: ReactiveMenu = reactive<Array<MenuCategory>>([
        {
            key: 'maintenance',
            label: computed(() => $gettext('System Maintenance')),
            icon: IconRouter,
            items: [
                {
                    key: 'settings',
                    label: computed(() => $gettext('System Settings')),
                    url: {
                        name: 'admin:settings:index'
                    },
                    visible: userAllowed(GlobalPermission.Settings)
                },
                {
                    key: 'branding',
                    label: computed(() => $gettext('Custom Branding')),
                    url: {
                        name: 'admin:branding:index'
                    },
                    visible: userAllowed(GlobalPermission.Settings)
                },
                {
                    key: 'logs',
                    label: computed(() => $gettext('System Logs')),
                    url: {
                        name: 'admin:logs:index'
                    },
                    visible: userAllowed(GlobalPermission.Logs)
                },
                {
                    key: 'storage_locations',
                    label: computed(() => $gettext('Storage Locations')),
                    url: {
                        name: 'admin:storage_locations:index'
                    },
                    visible: userAllowed(GlobalPermission.StorageLocations)
                },
                {
                    key: 'backups',
                    label: computed(() => $gettext('Backups')),
                    url: {
                        name: 'admin:backups:index'
                    },
                    visible: userAllowed(GlobalPermission.Backups)
                },
                {
                    key: 'debug',
                    label: computed(() => $gettext('System Debugger')),
                    url: {
                        name: 'admin:debug:index'
                    },
                    visible: userAllowed(GlobalPermission.All)
                },
                {
                    key: 'updates',
                    label: computed(() => $gettext('Update AzuraCast')),
                    url: {
                        name: 'admin:updates:index'
                    },
                    visible: userAllowed(GlobalPermission.All)
                }
            ]
        },
        {
            key: 'users',
            label: computed(() => $gettext('Users')),
            icon: IconGroups,
            items: [
                {
                    key: 'manage_users',
                    label: computed(() => $gettext('User Accounts')),
                    url: {
                        name: 'admin:users:index'
                    },
                    visible: userAllowed(GlobalPermission.All)
                },
                {
                    key: 'permissions',
                    label: computed(() => $gettext('Roles & Permissions')),
                    url: {
                        name: 'admin:permissions:index'
                    },
                    visible: userAllowed(GlobalPermission.All)
                },
                {
                    key: 'auditlog',
                    label: computed(() => $gettext('Audit Log')),
                    url: {
                        name: 'admin:auditlog:index'
                    },
                    visible: userAllowed(GlobalPermission.Logs)
                },
                {
                    key: 'api_keys',
                    label: computed(() => $gettext('API Keys')),
                    url: {
                        name: 'admin:api:index'
                    },
                    visible: userAllowed(GlobalPermission.ApiKeys)
                }
            ]
        },
        {
            key: 'stations',
            label: computed(() => $gettext('Stations')),
            icon: IconRadio,
            items: [
                {
                    key: 'manage_stations',
                    label: computed(() => $gettext('Stations')),
                    url: {
                        name: 'admin:stations:index'
                    },
                    visible: userAllowed(GlobalPermission.Stations)
                },
                {
                    key: 'custom_fields',
                    label: computed(() => $gettext('Custom Fields')),
                    url: {
                        name: 'admin:custom_fields:index'
                    },
                    visible: userAllowed(GlobalPermission.CustomFields)
                },
                {
                    key: 'relays',
                    label: computed(() => $gettext('Connected AzuraRelays')),
                    url: {
                        name: 'admin:relays:index',
                    },
                    visible: userAllowed(GlobalPermission.Stations)
                },
                {
                    key: 'shoutcast',
                    label: computed(() => $gettext('Install Shoutcast')),
                    url: {
                        name: 'admin:install_shoutcast:index'
                    },
                    visible: userAllowed(GlobalPermission.All)
                },
                {
                    key: 'stereo_tool',
                    label: computed(() => $gettext('Install Stereo Tool')),
                    url: {
                        name: 'admin:stereo_tool:index'
                    },
                    visible: userAllowed(GlobalPermission.All)
                },
                {
                    key: 'geolite',
                    label: computed(() => $gettext('Install GeoLite IP Database')),
                    url: {
                        name: 'admin:install_geolite:index'
                    },
                    visible: userAllowed(GlobalPermission.All)
                }
            ]
        }
    ]);

    return filterMenu(menu);
}
