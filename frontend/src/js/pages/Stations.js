import initApp from "~/layout";
import {h} from "vue";
import {createRouter, createWebHistory} from "vue-router";
import StationsLayout from "~/components/Stations/StationsLayout.vue";
import useStationsRoutes from "~/components/Stations/routes";
import {useAzuraCast} from "~/vendor/azuracast";
import {installRouter} from "~/vendor/router";

initApp({
    render() {
        return h(StationsLayout);
    }
}, (vueApp) => {
    const routes = useStationsRoutes();
    const {componentProps} = useAzuraCast();

    installRouter(
        createRouter({
            history: createWebHistory(componentProps.baseUrl),
            routes
        }),
        vueApp
    );
});


