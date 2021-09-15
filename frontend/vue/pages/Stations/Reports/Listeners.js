import '~/base.js';
import '~/vendor/bootstrapVue.js';
import '~/vendor/luxon.js';

import Vue
  from 'vue';

import Listeners
  from '~/components/Stations/Reports/Listeners.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(Listeners, {
        props: props
      });
    }
  });
}
