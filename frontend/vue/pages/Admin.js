import initApp from "~/layout";
import {h} from "vue";
import {createRouter, createWebHashHistory} from "vue-router";
import AdminLayout from "~/components/Admin/AdminLayout.vue";
import useAdminRoutes from "~/components/Admin/routes";
import installRouterLoading from "~/functions/installRouterLoading";

initApp({
    render() {
        return h(AdminLayout);
    }
}, async (vueApp) => {
    const routes = useAdminRoutes();

    const router = createRouter({
        history: createWebHashHistory(),
        routes
    });

    installRouterLoading(router);

    vueApp.use(router);
});
