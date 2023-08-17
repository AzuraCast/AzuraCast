import initApp from "~/layout";
import {h} from "vue";
import {createRouter, createWebHistory} from "vue-router";
import AdminLayout from "~/components/Admin/AdminLayout.vue";
import useAdminRoutes from "~/components/Admin/routes";
import {useAzuraCast} from "~/vendor/azuracast";
import {installRouter} from "~/vendor/router";

initApp({
    render() {
        return h(AdminLayout);
    }
}, async (vueApp) => {
    const routes = useAdminRoutes();
    const {componentProps} = useAzuraCast();

    installRouter(
        createRouter({
            history: createWebHistory(componentProps.baseUrl),
            routes
        }),
        vueApp
    );
});
