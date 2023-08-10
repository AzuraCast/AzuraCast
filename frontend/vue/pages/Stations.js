import initApp from "~/layout";
import {h} from "vue";
import {createRouter, createWebHashHistory} from "vue-router";
import StationsLayout from "~/components/Stations/StationsLayout.vue";
import useStationsRoutes from "~/components/Stations/routes";
import installRouterLoading from "~/functions/installRouterLoading";

initApp({
    render() {
        return h(StationsLayout);
    }
}, (vueApp) => {
    const routes = useStationsRoutes();

    const router = createRouter({
        history: createWebHashHistory(),
        routes
    });

    installRouterLoading(router);

    vueApp.use(router);
});


