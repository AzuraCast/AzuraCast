import {RouteRecordRaw} from "vue-router";

export default function useAdminRoutes(): RouteRecordRaw[] {
    return [
        {
            path: '/admin',
            component: () => import('~/components/Admin/Index.vue'),
            name: 'admin:index'
        },
        {
            path: '/admin',
            component: () => import('~/components/Admin/AdminSidebarWrapper.vue'),
            children: [
                {
                    path: 'api-keys',
                    component: () => import('~/components/Admin/ApiKeys.vue'),
                    name: 'admin:api-keys:index'
                },
                {
                    path: 'settings',
                    component: () => import('~/components/Admin/SettingsWrapper.vue'),
                    name: 'admin:settings:index'
                },
                {
                    path: 'branding',
                    component: () => import('~/components/Admin/Branding.vue'),
                    name: 'admin:branding:index'
                },
                {
                    path: 'logs',
                    component: () => import('~/components/Admin/Logs.vue'),
                    name: 'admin:logs:index'
                },
                {
                    path: 'storage_locations',
                    component: () => import('~/components/Admin/StorageLocations.vue'),
                    name: 'admin:storage_locations:index'
                },
                {
                    path: 'backups',
                    component: () => import('~/components/Admin/BackupsWrapper.vue'),
                    name: 'admin:backups:index'
                },
                {
                    path: 'debug',
                    component: () => import('~/components/Admin/Debug.vue'),
                    name: 'admin:debug:index'
                },
                {
                    path: 'updates',
                    component: () => import('~/components/Admin/Updates.vue'),
                    name: 'admin:updates:index'
                },
                {
                    path: 'users',
                    component: () => import('~/components/Admin/Users.vue'),
                    name: 'admin:users:index'
                },
                {
                    path: 'permissions',
                    component: () => import('~/components/Admin/PermissionsWrapper.vue'),
                    name: 'admin:permissions:index',
                },
                {
                    path: 'auditlog',
                    component: () => import('~/components/Admin/AuditLog.vue'),
                    name: 'admin:auditlog:index'
                },
                {
                    path: 'api_keys',
                    component: () => import('~/components/Admin/ApiKeys.vue'),
                    name: 'admin:api:index'
                },
                {
                    path: 'stations',
                    component: () => import('~/components/Admin/Stations.vue'),
                    name: 'admin:stations:index'
                },
                {
                    path: 'custom_fields',
                    component: () => import('~/components/Admin/CustomFields.vue'),
                    name: 'admin:custom_fields:index',
                },
                {
                    path: 'relays',
                    component: () => import('~/components/Admin/Relays.vue'),
                    name: 'admin:relays:index',
                },
                {
                    path: 'install_shoutcast',
                    component: () => import('~/components/Admin/Shoutcast.vue'),
                    name: 'admin:install_shoutcast:index'
                },
                {
                    path: 'install_rsas',
                    component: () => import('~/components/Admin/Rsas.vue'),
                    name: 'admin:install_rsas:index'
                },
                {
                    path: 'install_stereo_tool',
                    component: () => import('~/components/Admin/StereoTool.vue'),
                    name: 'admin:stereo_tool:index'
                },
                {
                    path: 'install_geolite',
                    component: () => import('~/components/Admin/GeoLite.vue'),
                    name: 'admin:install_geolite:index'
                }
            ],
        }
    ];
}
