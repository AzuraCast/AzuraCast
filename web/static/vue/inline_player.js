import InlinePlayer from './inline_player.vue'
import Vue from 'vue'
import './event_bus.js'
import './translations.js'

export default function init (options) {
  Vue.config.language = options.lang

  return new Vue({
    el: options.el,
    render: (h) => h(InlinePlayer)
  })
}