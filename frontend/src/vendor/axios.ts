import axios, {AxiosStatic} from "axios";
import VueAxios from "vue-axios";
import {App, inject, InjectionKey} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";
import {useAzuraCast} from "~/vendor/azuracast.ts";
import {useNProgress} from "~/vendor/nprogress.ts";

const injectKey: InjectionKey<AxiosStatic> = Symbol() as InjectionKey<AxiosStatic>;
const injectKeySilent: InjectionKey<AxiosStatic> = Symbol() as InjectionKey<AxiosStatic>;

/* Composition API Axios utilities */
interface UseAxios {
    axios: AxiosStatic,
    axiosSilent: AxiosStatic
}

export const useAxios = (): UseAxios => ({
    axios: inject<AxiosStatic>(injectKey),
    axiosSilent: inject<AxiosStatic>(injectKeySilent)
});

export default function installAxios(vueApp: App) {
    // Configure auto-CSRF on requests
    const {apiCsrf} = useAzuraCast();

    const axiosInstance = axios.create({
        headers: {
            'X-API-CSRF': apiCsrf
        }
    });

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

    const axiosSilent = axiosInstance.create({});

    const {setLoading} = useNProgress();

    axiosInstance.interceptors.request.use((config) => {
        setLoading(true);
        return config;
    }, (error) => {
        setLoading(false);
        handleAxiosError(error);
        return Promise.reject(error);
    });

    axiosInstance.interceptors.response.use((response) => {
        setLoading(false);
        return response;
    }, (error) => {
        setLoading(false);
        handleAxiosError(error);
        return Promise.reject(error);
    });

    vueApp.use(VueAxios, axiosInstance);

    vueApp.provide(injectKey, axiosInstance);
    vueApp.provide(injectKeySilent, axiosSilent);
}
