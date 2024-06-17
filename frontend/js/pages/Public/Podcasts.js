import initApp from "~/layout";
import {h} from "vue";
import {createRouter, createWebHistory} from "vue-router";
import {useAzuraCast} from "~/vendor/azuracast";
import {installRouter} from "~/vendor/router";
import PodcastsLayout from "~/components/Public/Podcasts/PodcastsLayout.vue";
import usePodcastRoutes from "~/components/Public/Podcasts/routes";

initApp({
    setup() {
        const {componentProps} = useAzuraCast();
        return {componentProps};
    },
    render() {
        return h(PodcastsLayout, this.componentProps);
    }
}, async (vueApp) => {
    const routes = usePodcastRoutes();
    const {componentProps} = useAzuraCast();

    installRouter(
        createRouter({
            history: createWebHistory(componentProps.baseUrl),
            routes
        }),
        vueApp
    );
});
