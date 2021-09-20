import '~/base.js';
import '~/vendor/bootstrapVue.js';

import Vue
  from 'vue';

import AdminCustomFields
  from '~/components/Admin/CustomFields.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(AdminCustomFields, {
        props: props
      });
    }
  });
}
