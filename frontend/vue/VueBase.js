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

  Vue.config.language = lang;

  // Configure auto-CSRF on requests
  axios.defaults.headers.common['X-API-CSRF'] = csrf;

  Vue.use(VueAxios, axios);
}
