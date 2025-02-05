import {useTranslate} from "~/vendor/gettext.ts";
import {GlobalPermissions, userAllowed} from "~/acl.ts";
import filterMenu, {MenuCategory, ReactiveMenu} from "~/functions/filterMenu.ts";
import {computed} from "vue";
import {IconGroups, IconRadio, IconRouter} from "~/components/Common/icons.ts";
import {reactiveComputed} from "@vueuse/core";

export function useAdminMenu(): ReactiveMenu {
    const {$gettext} = useTranslate();

    const menu: ReactiveMenu = reactiveComputed(
        () => {
            const maintenanceMenu: MenuCategory = {
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
                        visible: userAllowed(GlobalPermissions.Settings)
                    },
                    {
                        key: 'branding',
                        label: computed(() => $gettext('Custom Branding')),
                        url: {
                            name: 'admin:branding:index'
                        },
                        visible: userAllowed(GlobalPermissions.Settings)
                    },
                    {
                        key: 'logs',
                        label: computed(() => $gettext('System Logs')),
                        url: {
                            name: 'admin:logs:index'
                        },
                        visible: userAllowed(GlobalPermissions.Logs)
                    },
                    {
                        key: 'storage_locations',
                        label: computed(() => $gettext('Storage Locations')),
                        url: {
                            name: 'admin:storage_locations:index'
                        },
                        visible: userAllowed(GlobalPermissions.StorageLocations)
                    },
                    {
                        key: 'backups',
                        label: computed(() => $gettext('Backups')),
                        url: {
                            name: 'admin:backups:index'
                        },
                        visible: userAllowed(GlobalPermissions.Backups)
                    },
                    {
                        key: 'debug',
                        label: computed(() => $gettext('System Debugger')),
                        url: {
                            name: 'admin:debug:index'
                        },
                        visible: userAllowed(GlobalPermissions.All)
                    },
                    {
                        key: 'updates',
                        label: computed(() => $gettext('Update AzuraCast')),
                        url: {
                            name: 'admin:updates:index'
                        },
                        visible: userAllowed(GlobalPermissions.All)
                    }
                ]
            };

            const usersMenu: MenuCategory = {
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
                        visible: userAllowed(GlobalPermissions.All)
                    },
                    {
                        key: 'permissions',
                        label: computed(() => $gettext('Roles & Permissions')),
                        url: {
                            name: 'admin:permissions:index'
                        },
                        visible: userAllowed(GlobalPermissions.All)
                    },
                    {
                        key: 'auditlog',
                        label: computed(() => $gettext('Audit Log')),
                        url: {
                            name: 'admin:auditlog:index'
                        },
                        visible: userAllowed(GlobalPermissions.Logs)
                    },
                    {
                        key: 'api_keys',
                        label: computed(() => $gettext('API Keys')),
                        url: {
                            name: 'admin:api:index'
                        },
                        visible: userAllowed(GlobalPermissions.ApiKeys)
                    }
                ]
            };

            const stationsMenu: MenuCategory = {
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
                        visible: userAllowed(GlobalPermissions.Stations)
                    },
                    {
                        key: 'custom_fields',
                        label: computed(() => $gettext('Custom Fields')),
                        url: {
                            name: 'admin:custom_fields:index'
                        },
                        visible: userAllowed(GlobalPermissions.CustomFields)
                    },
                    {
                        key: 'relays',
                        label: computed(() => $gettext('Connected AzuraRelays')),
                        url: {
                            name: 'admin:relays:index',
                        },
                        visible: userAllowed(GlobalPermissions.Stations)
                    },
                    {
                        key: 'shoutcast',
                        label: computed(() => $gettext('Install Shoutcast')),
                        url: {
                            name: 'admin:install_shoutcast:index'
                        },
                        visible: userAllowed(GlobalPermissions.Settings)
                    },
                    {
                        key: 'rsas',
                        label: computed(() => $gettext('Install RSAS')),
                        url: {
                            name: 'admin:install_rsas:index'
                        },
                        visible: userAllowed(GlobalPermissions.Settings)
                    },
                    {
                        key: 'stereo_tool',
                        label: computed(() => $gettext('Install Stereo Tool')),
                        url: {
                            name: 'admin:stereo_tool:index'
                        },
                        visible: userAllowed(GlobalPermissions.Settings)
                    },
                    {
                        key: 'geolite',
                        label: computed(() => $gettext('Install GeoLite IP Database')),
                        url: {
                            name: 'admin:install_geolite:index'
                        },
                        visible: userAllowed(GlobalPermissions.Settings)
                    }
                ]
            };

            return {
                categories: [
                    maintenanceMenu,
                    usersMenu,
                    stationsMenu
                ]
            };
        }
    ) as unknown as ReactiveMenu;

    return filterMenu(menu);
}
