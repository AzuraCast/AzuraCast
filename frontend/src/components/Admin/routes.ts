import {getApiUrl} from "~/router.ts";
import populateComponentRemotely from "~/functions/populateComponentRemotely.ts";

export default function useAdminRoutes() {
    return [
        {
            path: '/',
            component: () => import('~/components/Admin/Index.vue'),
            name: 'admin:index'
        },
        {
            path: '/api-keys',
            component: () => import('~/components/Admin/ApiKeys.vue'),
            name: 'admin:api-keys:index'
        },
        {
            path: '/settings',
            name: 'admin:settings:index',
            component: () => import('~/components/Admin/Settings.vue'),
            ...populateComponentRemotely(getApiUrl('/admin/vue/settings'))
        },
        {
            path: '/branding',
            component: () => import('~/components/Admin/Branding.vue'),
            name: 'admin:branding:index'
        },
        {
            path: '/logs',
            name: 'admin:logs:index',
            component: () => import('~/components/Admin/Logs.vue'),
            ...populateComponentRemotely(getApiUrl('/admin/vue/logs'))
        },
        {
            path: '/storage_locations',
            component: () => import('~/components/Admin/StorageLocations.vue'),
            name: 'admin:storage_locations:index'
        },
        {
            path: '/backups',
            component: () => import('~/components/Admin/Backups.vue'),
            name: 'admin:backups:index',
            ...populateComponentRemotely(getApiUrl('/admin/vue/backups'))
        },
        {
            path: '/debug',
            component: () => import('~/components/Admin/Debug.vue'),
            name: 'admin:debug:index'
        },
        {
            path: '/updates',
            component: () => import('~/components/Admin/Updates.vue'),
            name: 'admin:updates:index',
            ...populateComponentRemotely(getApiUrl('/admin/vue/updates'))
        },
        {
            path: '/users',
            component: () => import('~/components/Admin/Users.vue'),
            name: 'admin:users:index',
            ...populateComponentRemotely(getApiUrl('/admin/vue/users'))
        },
        {
            path: '/permissions',
            component: () => import('~/components/Admin/Permissions.vue'),
            name: 'admin:permissions:index',
            ...populateComponentRemotely(getApiUrl('/admin/vue/permissions'))
        },
        {
            path: '/auditlog',
            component: () => import('~/components/Admin/AuditLog.vue'),
            name: 'admin:auditlog:index'
        },
        {
            path: '/api_keys',
            component: () => import('~/components/Admin/ApiKeys.vue'),
            name: 'admin:api:index'
        },
        {
            path: '/stations',
            component: () => import('~/components/Admin/Stations.vue'),
            name: 'admin:stations:index',
            ...populateComponentRemotely(getApiUrl('/admin/vue/stations'))
        },
        {
            path: '/custom_fields',
            component: () => import('~/components/Admin/CustomFields.vue'),
            name: 'admin:custom_fields:index',
            ...populateComponentRemotely(getApiUrl('/admin/vue/custom_fields'))
        },
        {
            path: '/relays',
            component: () => import('~/components/Admin/Relays.vue'),
            name: 'admin:relays:index',
        },
        {
            path: '/install_shoutcast',
            component: () => import('~/components/Admin/Shoutcast.vue'),
            name: 'admin:install_shoutcast:index'
        },
        {
            path: '/install_stereo_tool',
            component: () => import('~/components/Admin/StereoTool.vue'),
            name: 'admin:stereo_tool:index'
        },
        {
            path: '/install_geolite',
            component: () => import('~/components/Admin/GeoLite.vue'),
            name: 'admin:install_geolite:index'
        }
    ];
}
