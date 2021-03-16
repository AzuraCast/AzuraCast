import GetTextPlugin
  from 'vue-gettext';
import translations
  from '../../resources/locale/translations';

export default function (lang) {
  Vue.use(GetTextPlugin, {
    defaultLanguage: 'en_US',
    translations: translations,
    silent: true
  });

  Vue.config.language = lang;
}
