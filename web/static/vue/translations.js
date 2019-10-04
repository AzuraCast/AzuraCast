import Vue from 'vue'
import GetTextPlugin from 'vue-gettext'
import translations from '../../../resources/locale/translations'

Vue.use(GetTextPlugin, {
  defaultLanguage: 'en_US',
  translations: translations,
  silent: true
})