import Vue from 'vue';
import {BootstrapVue} from 'bootstrap-vue';

import 'bootstrap-vue/dist/bootstrap-vue.css';

Vue.use(BootstrapVue);

const BootstrapVueNotifiers = {
    install(Vue, opts) {
        Vue.prototype.$notify = function (message = null, options = {}) {
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

        Vue.prototype.$notifyError = function (message = null, options = {}) {
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

        Vue.prototype.$notifySuccess = function (message = null, options = {}) {
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

        Vue.prototype.$showLoading = function (message = null, options = {}) {
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

        Vue.prototype.$hideLoading = function () {
            this.$bvToast.hide(LOADING_TOAST_ID);
        };

        let $isAxiosLoading = false;
        let $axiosLoadCount = 0;

        Vue.prototype.$setLoading = function (isLoading) {
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

        Vue.prototype.$wrapWithLoading = function (promise) {
            this.$setLoading(true);

            promise.finally(() => {
                this.$setLoading(false);
            });

            return promise;
        };
    }
};

Vue.use(BootstrapVueNotifiers);
