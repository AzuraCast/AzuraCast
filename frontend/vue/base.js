import axios from 'axios';
import VueAxios from 'vue-axios';
import usePinia from './vendor/pinia';
import gettext from './vendor/gettext';
import {createApp} from "vue";
import useBootstrapVue from "./vendor/bootstrapVue";
import useSweetAlert from "./vendor/sweetalert";

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

        // Configure auto-CSRF on requests
        if (typeof App.api_csrf !== 'undefined') {
            axios.defaults.headers.common['X-API-CSRF'] = App.api_csrf;
        }

        vueApp.use(VueAxios, axios);

        vueApp.mixin({
            mounted() {
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

        /* Pinia */
        usePinia(vueApp);

        /* Bootstrap Vue */
        useBootstrapVue(vueApp);

        /* SweetAlert */
        useSweetAlert(vueApp);

        vueApp.mount(el);
    };
}
