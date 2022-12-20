import axios from "axios";
import VueAxios from "vue-axios";
import gettext from "~/vendor/gettext";
import {inject} from "vue";
import useAzuraCast from "~/vendor/azuracast";

/* Composition API Axios utilities */
export function useAxios() {
    return {
        axios: inject('axios')
    };
}

export default function installAxios(vueApp) {
    // Configure auto-CSRF on requests
    const {apiCsrf} = useAzuraCast();

    if (typeof apiCsrf !== 'undefined') {
        axios.defaults.headers.common['X-API-CSRF'] = apiCsrf;
    }

    vueApp.use(VueAxios, axios);

    vueApp.provide('axios', axios);

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
}
