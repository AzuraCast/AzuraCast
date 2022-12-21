import installPinia from './vendor/pinia';
import gettext from './vendor/gettext';
import {createApp, h} from "vue";
import installBootstrapVue from "./vendor/bootstrapVue";
import installSweetAlert from "./vendor/sweetalert";
import installAxios from "~/vendor/axios";
import useAzuraCast from "~/vendor/azuracast";
import axios from "axios";
import {useEventBus} from "@vueuse/core";

export default function (component) {
    const vueApp = createApp({
        render() {
            return h(component, this.$appProps)
        },
        mounted() {
            // Workaround to use BootstrapVue toast notifications in Vue 3 composition API.
            const notifyBus = useEventBus('notify');

            notifyBus.on((event, payload) => {
                if (event === 'show') {
                    this.$bvToast.toast(payload.message, payload.options);
                } else if (event === 'hide') {
                    this.$bvToast.hide(payload.id);
                }
            });

            // Configure some Axios settings that depend on the BootstrapVue $bvToast superglobal.
            const handleAxiosError = (error) => {
                const {$gettext} = gettext;

                let notifyMessage = $gettext('An error occurred and your request could not be completed.');
                if (error.response) {
                    // Request made and server responded
                    notifyMessage = error.response.data.message;
                    console.error(notifyMessage);
                } else if (error.request) {
                    // The request was made but no response was received
                    console.error(error.request);
                } else {
                    // Something happened in setting up the request that triggered an Error
                    console.error('Error', error.message);
                }

                if (typeof this.$notifyError === 'function') {
                    this.$notifyError(notifyMessage);
                }
            };

            axios.interceptors.request.use((config) => {
                return config;
            }, (error) => {
                handleAxiosError(error);
                return Promise.reject(error);
            });

            axios.interceptors.response.use((response) => {
                return response;
            }, (error) => {
                handleAxiosError(error);
                return Promise.reject(error);
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
