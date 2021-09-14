import '~/base.js';
import '~/vendor/bootstrapVue.js';

import Vue
  from 'vue';

import Remotes
  from '~/components/Stations/Remotes.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(Remotes, {
        props: props
      });
    }
  });
}
