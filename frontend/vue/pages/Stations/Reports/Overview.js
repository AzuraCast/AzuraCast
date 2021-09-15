import '~/base.js';
import '~/vendor/bootstrapVue.js';
import '~/vendor/chartjs.js';

import Vue
  from 'vue';

import Overview
  from '~/components/Stations/Reports/Overview.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(Overview, {
        props: props
      });
    }
  });
}
