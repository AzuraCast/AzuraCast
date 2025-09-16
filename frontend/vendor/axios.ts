/* eslint-disable @typescript-eslint/prefer-promise-reject-errors */

import axios, {AxiosError, AxiosInstance, AxiosRequestConfig, AxiosResponse} from "axios";
import {App, InjectionKey} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useAzuraCast} from "~/vendor/azuracast.ts";
import {useNProgress} from "~/vendor/nprogress.ts";
import injectRequired from "~/functions/injectRequired.ts";
import {ApiError} from "~/entities/ApiInterfaces.ts";

const injectKey: InjectionKey<AxiosInstance> = Symbol() as InjectionKey<AxiosInstance>;
const injectKeySilent: InjectionKey<AxiosInstance> = Symbol() as InjectionKey<AxiosInstance>;

/* Composition API Axios utilities */
interface UseAxios {
    axios: AxiosInstance,
    axiosSilent: AxiosInstance
}

export const useAxios = (): UseAxios => ({
    axios: injectRequired<AxiosInstance>(injectKey),
    axiosSilent: injectRequired<AxiosInstance>(injectKeySilent)
});

type AxiosApiError<D = any> = Omit<AxiosError<ApiError, D>, 'response'> & {
    response: AxiosResponse<ApiError, D>
}

export const isApiError = (payload: any): payload is AxiosApiError => {
    if (axios.isAxiosError(payload)) {
        if ('response' in payload && payload.response) {
            if ('data' in payload.response) {
                return 'success' in payload.response.data && 'message' in payload.response.data;
            }
        }
    }

    return false;
};

export const getErrorAsString = (error: any): string => {
    if (axios.isCancel(error)) {
        return `HTTP request cancelled: ${error.message}`;
    }

    if (axios.isAxiosError(error)) {
        if (isApiError(error)) {
            return error.response.data.message;
        }

        return error.message;
    }

    return String(error);
}

export default function installAxios(vueApp: App) {
    // Configure auto-CSRF on requests
    const {apiCsrf} = useAzuraCast();

    const config: AxiosRequestConfig = {
        headers: {
            "X-API-CSRF": apiCsrf
        }
    }

    // Configure some Axios settings that depend on the BootstrapVue $bvToast superglobal.
    const handleAxiosError = (error: any) => {
        const {$gettext} = useTranslate();

        // Canceled HTTP requests are expected.
        if (axios.isCancel(error)) {
            console.log('HTTP request cancelled:', error.message);
            return;
        }

        let notifyMessage = $gettext('An error occurred and your request could not be completed.');

        if (isApiError(error)) {
            // Request made and server responded
            const responseJson = error.response.data;

            // Immediately redirect back to login page if the HTTP request returns a 403 NotLoggedIn error.
            if (responseJson.type === "NotLoggedInException") {
                window.location.href = "/login";
                return;
            }

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

    const axiosInstance = axios.create(config);

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

    const axiosSilent = axios.create(config);

    axiosSilent.interceptors.request.use(
        (config) => (config),
        (error) => {
            handleAxiosError(error);

            return Promise.reject(error);
        }
    );

    axiosSilent.interceptors.response.use(
        (response) => (response),
        (error) => {
            handleAxiosError(error);

            return Promise.reject(error);
        }
    );

    vueApp.provide(injectKey, axiosInstance);
    vueApp.provide(injectKeySilent, axiosSilent);
}
