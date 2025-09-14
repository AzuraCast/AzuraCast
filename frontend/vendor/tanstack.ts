import {VueQueryPlugin, VueQueryPluginOptions} from "@tanstack/vue-query";
import {App} from "vue";

const vueQueryPluginOptions: VueQueryPluginOptions = {
    enableDevtoolsV6Plugin: true,
    queryClientConfig: {
        defaultOptions: {
            queries: {
                retryDelay: (attemptIndex) => Math.min(2500 * 2 ** attemptIndex, 30000),
            },
        },
    },
}

export default function installTanstack(vueApp: App) {
    vueApp.use(VueQueryPlugin, vueQueryPluginOptions)
}


