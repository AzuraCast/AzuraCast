import {App, createApp} from "vue";
import installAxios from "~/vendor/axios";
import {installTranslate} from "~/vendor/gettext";
import {installCurrentVueInstance} from "~/vendor/vueInstance";
import {AzuraCastConstants, setGlobalProps} from "~/vendor/azuracast";

interface InitApp {
    vueApp: App<Element>
}

export default function initApp(appConfig = {}, appCallback = null): InitApp {
    const vueApp: App<Element> = createApp(appConfig);

    /* Track current instance (for programmatic use). */
    installCurrentVueInstance(vueApp);

    (<any>window).vueComponent = async (el: string, globalProps: AzuraCastConstants): Promise<void> => {
        setGlobalProps(globalProps);

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
