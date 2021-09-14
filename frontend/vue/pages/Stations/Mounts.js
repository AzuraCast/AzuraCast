import '~/base.js';
import '~/vendor/bootstrapVue.js';

import Vue
  from 'vue';

import Mounts
  from '~/components/Stations/Mounts.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(Mounts, {
        props: props
      });
    }
  });
}
