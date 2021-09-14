import '~/base.js';
import '~/vendor/bootstrapVue.js';
import '~/vendor/fancybox.js';

import Vue
  from 'vue';

import AdminBranding
  from '~/components/Admin/Branding.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(AdminBranding, {
        props: props
      });
    }
  });
}
