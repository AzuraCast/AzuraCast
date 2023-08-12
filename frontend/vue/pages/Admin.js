import initApp from "~/layout";
import {h} from "vue";
import {createRouter, createWebHistory} from "vue-router";
import AdminLayout from "~/components/Admin/AdminLayout.vue";
import useAdminRoutes from "~/components/Admin/routes";
import installRouterLoading from "~/functions/installRouterLoading";
import {useAzuraCast} from "~/vendor/azuracast";

initApp({
    render() {
        return h(AdminLayout);
    }
}, async (vueApp) => {
    const routes = useAdminRoutes();

    const {componentProps} = useAzuraCast();

    const router = createRouter({
        history: createWebHistory(componentProps.baseUrl),
        routes
    });

    installRouterLoading(router);

    vueApp.use(router);
});
