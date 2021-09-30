import Vue
  from 'vue';
import axios
  from 'axios';
import VueAxios
  from 'vue-axios';
import GetTextPlugin
  from 'vue-gettext';
import translations
  from '../../resources/locale/translations.json';

document.addEventListener('DOMContentLoaded', function () {
  // Configure localization
  Vue.use(GetTextPlugin, {
    defaultLanguage: 'en_US',
    translations: translations,
    silent: true
  });

  if (typeof App.locale !== 'undefined') {
    Vue.config.language = App.locale;
  }

  // Configure auto-CSRF on requests
  if (typeof App.api_csrf !== 'undefined') {
    axios.defaults.headers.common['X-API-CSRF'] = App.api_csrf;
  }

  Vue.use(VueAxios, axios);

  const AxiosErrorHandler = {
    install (Vue, opts) {
      Vue.prototype.$handleAxiosError = function (error) {
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

        return notifyMessage;
      };
    }
  };

  Vue.use(AxiosErrorHandler);

  Vue.prototype.$eventHub = new Vue();
});
