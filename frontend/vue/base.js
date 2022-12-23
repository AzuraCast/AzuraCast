import {createApp, h} from "vue";
import installBootstrapVue from "./vendor/bootstrapVue";
import installSweetAlert from "./vendor/sweetalert";
import installAxios from "~/vendor/axios";
import {installPinia} from '~/vendor/pinia';
import {useNotifyBus} from "~/vendor/events";
import {installTranslate} from "~/vendor/gettext";

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
    installTranslate(vueApp);

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
