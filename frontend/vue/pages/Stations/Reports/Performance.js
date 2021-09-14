import '~/base.js';
import '~/vendor/bootstrapVue.js';

import Vue
  from 'vue';

import Performance
  from '~/components/Stations/Reports/Performance.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(Performance, {
        props: props
      });
    }
  });
}
