import {App, Component, createApp, reactive} from "vue";
import installAxios from "~/vendor/axios";
import {installTranslate} from "~/vendor/gettext";
import {installCurrentVueInstance} from "~/vendor/vueInstance";
import {globalConstantsKey} from "~/vendor/azuracast";
import installTanstack from "~/vendor/tanstack.ts";
import {createPinia} from "pinia";
import {VueAppGlobals} from "~/entities/ApiInterfaces.ts";

export default function initApp(
    appConfig: Component = {},
    appCallback?: (app: App<Element>) => Promise<void>
): {
    vueApp: App<Element>
} {
    const vueApp: App<Element> = createApp(appConfig);

    /* Track current instance (for programmatic use). */
    installCurrentVueInstance(vueApp);

    /* TanStack Query */
    installTanstack(vueApp);

    /* Pinia */
    const pinia = createPinia();
    vueApp.use(pinia);

    (<any>window).vueComponent = async (el: string, globalProps: VueAppGlobals): Promise<void> => {
        vueApp.provide(globalConstantsKey, reactive(globalProps));

        /* Gettext */
        await installTranslate(vueApp);

        /* Axios */
        installAxios(vueApp);

        if (typeof appCallback === 'function') {
            await appCallback(vueApp);
        }

        vueApp.mount(el);
    };

    return {
        vueApp
    };
}
