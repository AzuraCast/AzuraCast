import '~/init/base.js';
import '~/init/bootstrapVue.js';

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
