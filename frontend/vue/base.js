import axios from 'axios';
import VueAxios from 'vue-axios';
import GetTextPlugin from 'vue-gettext';
import translations from '../../translations/translations.json';
import pinia from './vendor/pinia';

export default function (vueApp) {
    return function (el, props) {
        document.addEventListener('DOMContentLoaded', function () {
            // Configure localization
            vueApp.use(GetTextPlugin, {
                defaultLanguage: 'en_US',
                translations: translations,
                silent: true
            });

            if (typeof App.locale !== 'undefined') {
                vueApp.config.language = App.locale;
            }

            // Configure auto-CSRF on requests
            if (typeof App.api_csrf !== 'undefined') {
                axios.defaults.headers.common['X-API-CSRF'] = App.api_csrf;
            }

            vueApp.use(VueAxios, axios);
        });

        vueApp.created(() => {
            let handleAxiosError = (error) => {
                let notifyMessage = this.$gettext('An error occurred and your request could not be completed.');
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
        });

        vueApp.mount(el, {props: props});
    };
}
