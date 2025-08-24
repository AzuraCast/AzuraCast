import {Router} from "vue-router";
import {App, ComputedRef} from "vue";
import {useAxios} from "~/vendor/axios.ts";
import NProgress from "nprogress";
import {getApiUrl} from "~/router.ts";

export function installRouter(router: Router, vueApp: App): void {
    // Add remote prop loading support
    router.beforeEach(async (to, _, next) => {
        if (to.name) {
            NProgress.start();
        }

        if (to.meta.remoteUrl) {
            const remoteUrlBase = String(to.meta.remoteUrl as string);
            let remoteUrl: ComputedRef<string>;

            if (to.params.station_id) {
                const stationId = String(to.params.station_id);
                remoteUrl = getApiUrl(
                    `/station/${stationId}${remoteUrlBase}`
                );
            } else {
                remoteUrl = getApiUrl(remoteUrlBase);
            }

            const {axiosSilent: axios} = useAxios();
            to.meta.state = await axios.get(remoteUrl.value).then(r => r.data);
        }
        next();
    });

    // Add NProgress displays
    router.afterEach(() => {
        NProgress.done();
    });

    vueApp.use(router);
}
