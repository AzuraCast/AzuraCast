import '~/base.js';
import '~/vendor/bootstrapVue.js';
import '~/vendor/luxon.js';

import Vue
  from 'vue';

import AuditLog
  from '~/components/Admin/AuditLog.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(AuditLog, {
        props: props
      });
    }
  });
}
