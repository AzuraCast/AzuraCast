import installPinia from './vendor/pinia';
import gettext from './vendor/gettext';
import {createApp, h} from "vue";
import installBootstrapVue from "./vendor/bootstrapVue";
import installSweetAlert from "./vendor/sweetalert";
import installAxios from "~/vendor/axios";
import useAzuraCast from "~/vendor/azuracast";
import {useNotifyBus} from "~/vendor/events";

export default function (component) {
    const vueApp = createApp({
        render() {
            return h(component, this.$appProps)
        },
        mounted() {
            // Workaround to use BootstrapVue toast notifications in Vue 3 composition API.
            const notifyBus = useNotifyBus();

            notifyBus.on((event, payload) => {
                if (event === 'show') {
                    this.$bvToast.toast(payload.message, payload.options);
                } else if (event === 'hide') {
                    this.$bvToast.hide(payload.id);
                }
            });
        }
    });

    /* Gettext */
    const {locale} = useAzuraCast();

    if (typeof locale !== 'undefined') {
        vueApp.config.language = locale;
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

    return function (el, props) {
        vueApp.config.globalProperties.$appProps = props;
        vueApp.mount(el);
    };
}
