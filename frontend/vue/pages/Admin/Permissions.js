import '~/base.js';
import '~/vendor/bootstrapVue.js';

import Vue
  from 'vue';

import AdminPermissions
  from '~/components/Admin/Permissions.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(AdminPermissions, {
        props: props
      });
    }
  });
}
