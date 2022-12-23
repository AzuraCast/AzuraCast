import {BootstrapVue} from 'bootstrap-vue';

import 'bootstrap-vue/dist/bootstrap-vue.css';
import {useTranslate} from "~/vendor/gettext";
import {useNotifyBus} from "~/vendor/events";

/* Composition API BootstrapVue utilities */
export function useNotify() {
    const {$gettext} = useTranslate();
    const notifyBus = useNotifyBus();

    const notify = (message = null, options = {}) => {
        if (!!document.hidden) {
            return;
        }

        const defaults = {
            variant: 'default',
            toaster: 'b-toaster-top-right',
            autoHideDelay: 3000,
            solid: true
        };

        notifyBus.emit('show', {
            message: message,
            options: {...defaults, ...options}
        });
    };

    const notifyError = (message = null, options = {}) => {
        if (message === null) {
            message = $gettext('An error occurred and your request could not be completed.');
        }

        const defaults = {
            variant: 'danger',
            title: $gettext('Error')
        };

        notify(message, {...defaults, ...options});

        return message;
    };

    const notifySuccess = (message = null, options = {}) => {
        if (message === null) {
            message = $gettext('Changes saved.');
        }

        const defaults = {
            variant: 'success',
            title: $gettext('Success')
        };

        notify(message, {...defaults, ...options});

        return message;
    };

    const LOADING_TOAST_ID = 'toast-loading';

    const showLoading = (message = null, options = {}) => {
        if (message === null) {
            message = $gettext('Applying changes...');
        }

        const defaults = {
            id: LOADING_TOAST_ID,
            variant: 'warning',
            title: $gettext('Please wait...'),
            autoHideDelay: 10000,
            isStatus: true
        };

        notify(message, {...defaults, ...options});
        return message;
    };

    const hideLoading = () => {
        notifyBus.emit('hide', {
            id: LOADING_TOAST_ID
        });
    };

    let $isAxiosLoading = false;
    let $axiosLoadCount = 0;

    const setLoading = (isLoading) => {
        let prevIsLoading = $isAxiosLoading;
        if (isLoading) {
            $axiosLoadCount++;
            $isAxiosLoading = true;
        } else if ($axiosLoadCount > 0) {
            $axiosLoadCount--;
            $isAxiosLoading = ($axiosLoadCount > 0);
        }

        // Handle state changes
        if (!prevIsLoading && $isAxiosLoading) {
            showLoading();
        } else if (prevIsLoading && !$isAxiosLoading) {
            hideLoading();
        }
    };

    const wrapWithLoading = (promise) => {
        setLoading(true);

        promise.finally(() => {
            setLoading(false);
        });

        return promise;
    };

    return {
        install(app) {
            app.config.globalProperties.$notify = notify;
            app.config.globalProperties.$notifyError = notifyError;
            app.config.globalProperties.$notifySuccess = notifySuccess;
            app.config.globalProperties.$showLoading = showLoading;
            app.config.globalProperties.$hideLoading = hideLoading;
            app.config.globalProperties.$setLoading = setLoading;
            app.config.globalProperties.$wrapWithLoading = wrapWithLoading;
        },
        notify,
        notifyError,
        notifySuccess,
        showLoading,
        hideLoading,
        setLoading,
        wrapWithLoading
    };
}

export default function installBootstrapVue(vueApp) {
    vueApp.use(BootstrapVue);
    vueApp.use(useNotify());
};
