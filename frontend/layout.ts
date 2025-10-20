import {App, Component, createApp, h, reactive} from "vue";
import installAxios from "~/vendor/axios";
import {installTranslate} from "~/vendor/gettext";
import {globalConstantsKey, useAzuraCast} from "~/vendor/azuracast";
import installTanstack from "~/vendor/tanstack.ts";
import {createPinia} from "pinia";
import {VueAppGlobals} from "~/entities/ApiInterfaces.ts";
import AppWrapper from "~/components/Layout/AppWrapper.vue";
import {createHead} from "@unhead/vue/client";

export default function initApp(
    appConfig: Component = {},
    appCallback?: (app: App<Element>) => Promise<void>
): {
    vueApp: App<Element>
} {
    const vueApp: App<Element> = createApp({
        setup() {
            const {componentProps} = useAzuraCast();
            return {
                componentProps
            }
        },
        render() {
            return h(
                AppWrapper,
                {},
                {
                    default: () => h(appConfig, this.componentProps, {})
                }
            );
        }
    });

    /* TanStack Query */
    installTanstack(vueApp);

    /* Pinia */
    const pinia = createPinia();
    vueApp.use(pinia);

    /* Unhead */
    const head = createHead();
    vueApp.use(head);

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
