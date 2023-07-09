import {useTranslate} from "~/vendor/gettext";
import {useProgrammatic} from "@oruga-ui/oruga-next";

/* Composition API BootstrapVue utilities */
export function useNotify() {
    const {$gettext} = useTranslate();
    const {oruga} = useProgrammatic();

    const notify = (message = null, options = {}) => {
        if (document.hidden) {
            return;
        }

        const defaults = {
            rootClass: 'toast-notification',
            duration: 3000,
            position: 'top-right',
            closable: true
        };

        oruga.notification.open({
            ...defaults,
            ...options,
            message: message
        });
    };

    const notifyError = (message = null, options = {}) => {
        if (message === null) {
            message = $gettext('An error occurred and your request could not be completed.');
        }

        const defaults = {
            variant: 'danger'
        };

        notify(message, {...defaults, ...options});

        return message;
    };

    const notifySuccess = (message = null, options = {}) => {
        if (message === null) {
            message = $gettext('Changes saved.');
        }

        const defaults = {
            variant: 'success'
        };

        notify(message, {...defaults, ...options});

        return message;
    };

    let $loadingComponent;

    const showLoading = () => {
        $loadingComponent = oruga.loading.open({
            fullPage: true,
            container: null
        });
        setTimeout(() => $loadingComponent.close(), 3 * 1000);
    };

    const hideLoading = () => {
        $loadingComponent.close();
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
        notify,
        notifyError,
        notifySuccess,
        showLoading,
        hideLoading,
        setLoading,
        wrapWithLoading
    };
}
