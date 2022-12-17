import installPinia from './vendor/pinia';
import gettext from './vendor/gettext';
import {createApp} from "vue";
import installBootstrapVue from "./vendor/bootstrapVue";
import installSweetAlert from "./vendor/sweetalert";
import installAxios from "~/vendor/axios";

export default function (component, options) {
    return function (el, props) {
        const vueApp = createApp(
            component,
            {
                ...options,
                ...props
            }
        );

        /* Gettext */
        if (typeof App.locale !== 'undefined') {
            vueApp.config.language = App.locale;
        }

        vueApp.use(gettext);

        /* Axios */
        installAxios(vueApp);

        /* Pinia */
        installPinia(vueApp);

        /* Bootstrap Vue */
        installBootstrapVue(vueApp);

        /* SweetAlert */
        installSweetAlert(vueApp);

        vueApp.mount(el);
    };
}
