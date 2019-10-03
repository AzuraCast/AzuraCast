import Vue from 'vue'

if (!Vue.prototype.$eventHub) {
  Vue.prototype.$eventHub = new Vue()
}