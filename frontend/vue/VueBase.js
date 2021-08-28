import axios
  from 'axios';
import VueAxios
  from 'vue-axios';
import GetTextPlugin
  from 'vue-gettext';
import translations
  from '../../resources/locale/translations';

export default function (lang, csrf) {
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
}
