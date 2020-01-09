// TODO:
// This file is not used until the full Vue app transition.

import Vue from 'vue'

if (!Vue.prototype.$eventHub) {
  Vue.prototype.$eventHub = new Vue()
}