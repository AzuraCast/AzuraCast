import {App, createApp} from "vue";
import installAxios from "~/vendor/axios";
import {installPinia} from '~/vendor/pinia';
import {installTranslate} from "~/vendor/gettext";
import Oruga from "@oruga-ui/oruga-next";
import {bootstrapConfig} from "@oruga-ui/theme-bootstrap";
import {installCurrentVueInstance} from "~/vendor/vueInstance";
import {AzuraCastConstants, setGlobalProps} from "~/vendor/azuracast";

interface InitApp {
    vueApp: App<Element>
}

export default function initApp(appConfig = {}, appCallback = null): InitApp {
    const vueApp: App<Element> = createApp(appConfig);

    /* Track current instance (for programmatic use). */
    installCurrentVueInstance(vueApp);

    /* Pinia */
    installPinia(vueApp);

    /* Oruga */
    vueApp.use(Oruga, {
        ...bootstrapConfig,
        iconPack: 'mdi',
        modal: {
            ...bootstrapConfig.modal,
            contentClass: "modal-dialog",
        },
        pagination: {
            ...bootstrapConfig.pagination,
            orderClass: '',
        },
        tabs: {
            ...bootstrapConfig.tabs,
            animated: false
        },
        notification: {
            ...bootstrapConfig.notification,
            rootClass: (_, {props}) => {
                const classes = ['alert', 'notification'];
                if (props.variant)
                    classes.push(`text-bg-${props.variant}`);
                return classes.join(' ');
            },
        }
    });

    window.vueComponent = (el: string, globalProps: AzuraCastConstants): void => {
        setGlobalProps(globalProps);

        /* Gettext */
        installTranslate(vueApp);

        /* Axios */
        installAxios(vueApp);

        if (typeof appCallback === 'function') {
            appCallback(vueApp);
        }

        vueApp.mount(el);
    };

    return {
        vueApp
    };
}
