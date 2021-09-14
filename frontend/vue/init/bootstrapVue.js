import Vue
  from 'vue';
import { BootstrapVue } from 'bootstrap-vue';

// Import Bootstrap an BootstrapVue CSS files (order is important)
import 'bootstrap-vue/dist/bootstrap-vue.css';

// Make BootstrapVue available throughout your project
document.addEventListener('DOMContentLoaded', function () {
  Vue.use(BootstrapVue);
});
