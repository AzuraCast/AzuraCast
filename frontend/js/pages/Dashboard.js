import initApp from "~/layout";
import {createRouter, createWebHistory} from "vue-router";
import useAdminRoutes from "~/components/Admin/routes.js";
import DashboardWrapper from "~/components/DashboardWrapper.vue";
import useStationsRoutes from "~/components/Stations/routes.js";

initApp(DashboardWrapper, async (vueApp) => {
    const router = createRouter({
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
            ...useAdminRoutes(),
            ...useStationsRoutes()
        ],
        scrollBehavior(to, from, savedPosition) {
            if (savedPosition) {
                return savedPosition
            } else {
                return {top: 0}
            }
        },
    });

    vueApp.use(router);
});

