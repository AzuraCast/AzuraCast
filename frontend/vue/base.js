import {createApp, h} from "vue";
import installSweetAlert from "./vendor/sweetalert";
import installAxios from "~/vendor/axios";
import {installPinia} from '~/vendor/pinia';
import {installTranslate} from "~/vendor/gettext";
import Oruga from "@oruga-ui/oruga-next";
import {bootstrapConfig} from "@oruga-ui/theme-bootstrap";

export default function (component) {
    const vueApp = createApp({
        render() {
            return h(component, this.$appProps)
        },
    });

    /* Gettext */
    installTranslate(vueApp);

    /* Axios */
    installAxios(vueApp);

    /* Pinia */
    installPinia(vueApp);

    /* Oruga */
    vueApp.use(Oruga, {
        ...bootstrapConfig,
        pagination: {
            ...bootstrapConfig.pagination,
            orderClass: '',
        }
    });

    /* SweetAlert */
    installSweetAlert(vueApp);

    return function (el, props) {
        vueApp.config.globalProperties.$appProps = props;
        vueApp.mount(el);
    };
}
