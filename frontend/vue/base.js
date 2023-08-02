import {createApp, h} from "vue";
import installAxios from "~/vendor/axios";
import {installPinia} from '~/vendor/pinia';
import {installTranslate} from "~/vendor/gettext";
import Oruga from "@oruga-ui/oruga-next";
import {bootstrapConfig} from "@oruga-ui/theme-bootstrap";
import {installCurrentVueInstance} from "~/vendor/vueInstance";

export default function (component) {
    const vueApp = createApp({
        render() {
            return h(component, this.$appProps)
        },
    });

    /* Track current instance (for programmatic use). */
    installCurrentVueInstance(vueApp);

    /* Gettext */
    installTranslate(vueApp);

    /* Axios */
    installAxios(vueApp);

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

    const vueComponent = (el, props) => {
        vueApp.config.globalProperties.$appProps = props;
        vueApp.mount(el);
    }

    window.vueComponent = vueComponent;
    return vueComponent;
}
