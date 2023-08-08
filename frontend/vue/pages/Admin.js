import initApp from "~/layout";
import {h, toValue} from "vue";
import {createRouter, createWebHashHistory} from "vue-router";
import AdminLayout from "~/components/Admin/AdminLayout.vue";
import {getApiUrl} from "~/router";
import axios from "axios";

const {vueApp} = initApp({
    render() {
        return h(AdminLayout);
    }
});

const populateComponentRemotely = (url) => {
    return {
        beforeEnter: (to, from, next) => {
            axios.get(toValue(url)).then((resp) => {
                Object.assign(to.meta, {
                    state: resp.data
                });
                next();
            });
        },
        props: (route) => ({
            ...route.meta.state
        })
    }
}

const routes = [
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
        ...populateComponentRemotely(getApiUrl('/vue/admin/settings'))
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
        ...populateComponentRemotely(getApiUrl('/vue/admin/logs'))
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
        ...populateComponentRemotely(getApiUrl('/vue/admin/backups'))
    },
    {
        path: '/debug',
        component: () => import('~/components/Admin/Debug.vue'),
        name: 'admin:debug:index',
        ...populateComponentRemotely(getApiUrl('/vue/admin/debug'))
    },
    {
        path: '/updates',
        component: () => import('~/components/Admin/Updates.vue'),
        name: 'admin:updates:index',
        ...populateComponentRemotely(getApiUrl('/vue/admin/updates'))
    },
    {
        path: '/users',
        component: () => import('~/components/Admin/Users.vue'),
        name: 'admin:users:index',
        ...populateComponentRemotely(getApiUrl('/vue/admin/users'))
    },
    {
        path: '/permissions',
        component: () => import('~/components/Admin/Permissions.vue'),
        name: 'admin:permissions:index',
        ...populateComponentRemotely(getApiUrl('/vue/admin/permissions'))
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
        ...populateComponentRemotely(getApiUrl('/vue/admin/stations'))
    },
    {
        path: '/custom_fields',
        component: () => import('~/components/Admin/CustomFields.vue'),
        name: 'admin:custom_fields:index',
        ...populateComponentRemotely(getApiUrl('/vue/admin/custom_fields'))
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
]

const router = createRouter({
    history: createWebHashHistory(),
    routes,
});

vueApp.use(router);
