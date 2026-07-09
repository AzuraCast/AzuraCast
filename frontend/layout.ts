import { RegleVuePlugin } from "@regle/core";
import { createHead } from "@unhead/vue/client";
import { createPinia } from "pinia";
import { App, Component, createApp, h, reactive } from "vue";
import AppWrapper from "~/components/Layout/AppWrapper.vue";
import { VueAppGlobals } from "~/entities/ApiInterfaces.ts";
import installAxios from "~/vendor/axios";
import { globalConstantsKey, useAzuraCast } from "~/vendor/azuracast";
import { installTranslate } from "~/vendor/gettext";
import installTanstack from "~/vendor/tanstack.ts";

export default function initApp(
    appConfig: Component = {},
    appCallback?: (app: App<Element>) => Promise<void>,
): {
    vueApp: App<Element>;
} {
    const vueApp: App<Element> = createApp({
        setup() {
            const { componentProps } = useAzuraCast();
            return {
                componentProps,
            };
        },
        render() {
            return h(
                AppWrapper,
                {},
                {
                    default: () => h(appConfig, this.componentProps, {}),
                },
            );
        },
    });

    /* TanStack Query */
    installTanstack(vueApp);

    /* Pinia */
    const pinia = createPinia();
    vueApp.use(pinia);

    /* Unhead */
    const head = createHead();
    vueApp.use(head);

    /* Regle Dev Tools */
    vueApp.use(RegleVuePlugin);

    (<any>window).vueComponent = async (
        el: string,
        globalProps: VueAppGlobals,
    ): Promise<void> => {
        vueApp.provide(globalConstantsKey, reactive(globalProps));

        /* Gettext */
        await installTranslate(vueApp);

        /* Axios */
        installAxios(vueApp);

        if (typeof appCallback === "function") {
            await appCallback(vueApp);
        }

        vueApp.mount(el);
    };

    return {
        vueApp,
    };
}
