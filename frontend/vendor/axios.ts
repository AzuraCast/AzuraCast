import axios, {AxiosInstance, AxiosRequestConfig} from "axios";
import VueAxios from "vue-axios";
import {App, inject, InjectionKey} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";
import {useAzuraCast} from "~/vendor/azuracast.ts";
import {useNProgress} from "~/vendor/nprogress.ts";

const injectKey: InjectionKey<AxiosInstance> = Symbol() as InjectionKey<AxiosInstance>;
const injectKeySilent: InjectionKey<AxiosInstance> = Symbol() as InjectionKey<AxiosInstance>;

/* Composition API Axios utilities */
interface UseAxios {
    axios: AxiosInstance,
    axiosSilent: AxiosInstance
}

export const useAxios = (): UseAxios => ({
    axios: inject<AxiosInstance>(injectKey),
    axiosSilent: inject<AxiosInstance>(injectKeySilent)
});

export default function installAxios(vueApp: App) {
    // Configure auto-CSRF on requests
    const {apiCsrf} = useAzuraCast();

    const config: AxiosRequestConfig = {
        headers: {
            "X-API-CSRF": apiCsrf
        }
    }

    const axiosInstance = axios.create(config);
    const axiosSilent = axios.create(config);

    // Configure some Axios settings that depend on the BootstrapVue $bvToast superglobal.
    const handleAxiosError = (error) => {
        const {$gettext} = useTranslate();

        let notifyMessage = $gettext('An error occurred and your request could not be completed.');
        if (error.response) {
            // Request made and server responded
            const responseJson = error.response.data ?? {};
            notifyMessage = responseJson.message ?? notifyMessage;
            console.error(responseJson);
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
