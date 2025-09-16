import initApp from "~/layout";
import {h} from "vue";
import {createRouter, createWebHistory} from "vue-router";
import {useAzuraCast} from "~/vendor/azuracast";
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
    const {componentProps} = useAzuraCast();

    const router = createRouter({
        history: createWebHistory(componentProps.baseUrl),
        routes: usePodcastRoutes()
    });
    vueApp.use(router);
});
