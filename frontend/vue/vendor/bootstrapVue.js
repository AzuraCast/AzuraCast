import {BootstrapVue} from 'bootstrap-vue';

import 'bootstrap-vue/dist/bootstrap-vue.css';
import {inject} from "vue";
import gettext from "~/vendor/gettext";

/* Composition API BootstrapVue utilities */
export function useNotify() {
    const $bvToast = inject('bvToast');
    const {$gettext} = gettext;

    const notify = function (message = null, options = {}) {
        if (!!document.hidden) {
            return;
        }

        const defaults = {
            variant: 'default',
            toaster: 'b-toaster-top-right',
            autoHideDelay: 3000,
            solid: true
        };

        $bvToast.toast(message, {...defaults, ...options});
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
        $bvToast.hide(LOADING_TOAST_ID);
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

export default function installBootstrapVue(vueApp) {
    vueApp.use(BootstrapVue);

    vueApp.provide('bvToast', vueApp.config.globalProperties.$bvToast);
    vueApp.provide('bvModal', vueApp.config.globalProperties.$bvModal);

    vueApp.config.globalProperties.$notify = function (message = null, options = {}) {
        if (!!document.hidden) {
            return;
        }

        const defaults = {
            variant: 'default',
            toaster: 'b-toaster-top-right',
            autoHideDelay: 3000,
            solid: true
        };

        this.$bvToast.toast(message, {...defaults, ...options});
    };

    vueApp.config.globalProperties.$notifyError = function (message = null, options = {}) {
        if (message === null) {
            message = this.$gettext('An error occurred and your request could not be completed.');
        }

        const defaults = {
            variant: 'danger',
            title: this.$gettext('Error')
        };

        this.$notify(message, {...defaults, ...options});

        return message;
    };

    vueApp.config.globalProperties.$notifySuccess = function (message = null, options = {}) {
        if (message === null) {
            message = this.$gettext('Changes saved.');
        }

        const defaults = {
            variant: 'success',
            title: this.$gettext('Success')
        };

        this.$notify(message, {...defaults, ...options});

        return message;
    };

    const LOADING_TOAST_ID = 'toast-loading';

    vueApp.config.globalProperties.$showLoading = function (message = null, options = {}) {
        if (message === null) {
            message = this.$gettext('Applying changes...');
        }

        const defaults = {
            id: LOADING_TOAST_ID,
            variant: 'warning',
            title: this.$gettext('Please wait...'),
            autoHideDelay: 10000,
            isStatus: true
        };

        this.$notify(message, {...defaults, ...options});
        return message;
    };

    vueApp.config.globalProperties.$hideLoading = function () {
        this.$bvToast.hide(LOADING_TOAST_ID);
    };

    let $isAxiosLoading = false;
    let $axiosLoadCount = 0;

    vueApp.config.globalProperties.$setLoading = function (isLoading) {
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
            this.$showLoading();
        } else if (prevIsLoading && !$isAxiosLoading) {
            this.$hideLoading();
        }
    };

    vueApp.config.globalProperties.$wrapWithLoading = function (promise) {
        this.$setLoading(true);

        promise.finally(() => {
            this.$setLoading(false);
        });

        return promise;
    };
};
