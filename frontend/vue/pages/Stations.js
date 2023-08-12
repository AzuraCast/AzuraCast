import initApp from "~/layout";
import {h} from "vue";
import {createRouter, createWebHistory} from "vue-router";
import StationsLayout from "~/components/Stations/StationsLayout.vue";
import useStationsRoutes from "~/components/Stations/routes";
import installRouterLoading from "~/functions/installRouterLoading";
import {useAzuraCast} from "~/vendor/azuracast";

initApp({
    render() {
        return h(StationsLayout);
    }
}, (vueApp) => {
    const routes = useStationsRoutes();

    const {componentProps} = useAzuraCast();

    const router = createRouter({
        history: createWebHistory(componentProps.baseUrl),
        routes
    });

    installRouterLoading(router);

    vueApp.use(router);
});


