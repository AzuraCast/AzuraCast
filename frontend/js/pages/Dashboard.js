import initApp from "~/layout";
import PageWrapper from "~/components/PageWrapper.vue";
import {installRouter} from "~/vendor/router.js";
import {createRouter, createWebHistory} from "vue-router";
import useAdminRoutes from "~/components/Admin/routes.js";

initApp(PageWrapper, async (vueApp) => {
    installRouter(
        createRouter({
            history: createWebHistory('/'),
            routes: [
                {
                    path: '/dashboard',
                    component: () => import('~/components/Dashboard.vue'),
                    name: 'dashboard'
                },
                {
                    path: '/profile',
                    component: () => import('~/components/Account.vue'),
                    name: 'profile:index'
                },
                {
                    path: '/admin',
                    component: () => import('~/components/Admin/AdminLayout.vue'),
                    children: useAdminRoutes(),
                },
                {
                    path: '/station/:station_id',
                    component: () => import('~/components/Stations/StationsLayout.vue'),
                    // children: useStationsRoutes()
                }
            ]
        }),
        vueApp
    );
});

