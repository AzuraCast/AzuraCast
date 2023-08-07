import {createApp} from "vue";
import installAxios from "~/vendor/axios";
import {installPinia} from '~/vendor/pinia';
import {installTranslate} from "~/vendor/gettext";
import Oruga from "@oruga-ui/oruga-next";
import {bootstrapConfig} from "@oruga-ui/theme-bootstrap";
import {installCurrentVueInstance} from "~/vendor/vueInstance";
import {installGlobalProps} from "~/vendor/azuracast";

export default function initApp(appConfig = {}) {
    const vueApp = createApp(appConfig);

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

    window.vueComponent = (el, globalProps) => {
        installGlobalProps(vueApp, globalProps);

        /* Gettext */
        installTranslate(vueApp, globalProps.locale ?? 'en_US');

        /* Axios */
        installAxios(vueApp, globalProps.apiCsrf ?? null);

        vueApp.mount(el);
    };

    return {
        vueApp
    };
}
