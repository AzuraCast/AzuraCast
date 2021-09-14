import '~/base.js';
import '~/vendor/bootstrapVue.js';

import Vue
  from 'vue';

import Schedule
  from '~/components/Public/Schedule.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(Schedule, {
        props: props
      });
    }
  });
}
