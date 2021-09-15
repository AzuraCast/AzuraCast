import '~/base.js';
import '~/vendor/bootstrapVue.js';
import '~/vendor/luxon.js';

import Vue
  from 'vue';

import Requests
  from '~/components/Stations/Reports/Requests.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(Requests, {
        props: props
      });
    }
  });
}
