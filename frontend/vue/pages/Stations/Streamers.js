import '~/base.js';
import '~/vendor/bootstrapVue.js';
import '~/store.js';
import '~/vendor/luxon.js';

import Vue
  from 'vue';

import Streamers
  from '~/components/Stations/Streamers.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(Streamers, {
        props: props
      });
    }
  });
}
