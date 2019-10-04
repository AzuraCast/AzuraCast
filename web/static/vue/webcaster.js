import Webcaster from './webcaster.vue'
import Vue from 'vue'
import './event_bus.js'
import './translations.js'

export default function init (options) {
  Vue.config.language = options.lang

  return new Vue({
    el: options.el,
    render: (h) => h(Webcaster, {
      props: options.props
    })
  })
}