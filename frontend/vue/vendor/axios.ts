import axios from "axios";
import VueAxios from "vue-axios";
import {inject} from "vue";
import {useAzuraCast} from "~/vendor/azuracast";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";
import {InjectionKey} from "vue/dist/vue";

const injectKey= Symbol() as InjectionKey<axios>;

/* Composition API Axios utilities */
export function useAxios() {
    return {
        axios: inject(injectKey)
    };
}

export default function installAxios(vueApp) {
    // Configure auto-CSRF on requests
    const {apiCsrf} = useAzuraCast();

    if (typeof apiCsrf !== 'undefined') {
        axios.defaults.headers.common['X-API-CSRF'] = apiCsrf;
    }

    // Configure some Axios settings that depend on the BootstrapVue $bvToast superglobal.
    const handleAxiosError = (error) => {
        const {$gettext} = useTranslate();

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

        const {notifyError} = useNotify();
        notifyError(notifyMessage);
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

    vueApp.use(VueAxios, axios);

    vueApp.provide(injectKey, axios);
}
