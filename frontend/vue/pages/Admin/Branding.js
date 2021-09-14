import '~/init/base.js';
import '~/init/bootstrapVue.js';
import '~/init/fancybox.js';

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
