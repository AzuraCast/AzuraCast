import '~/init/base.js';
import '~/init/bootstrapVue.js';

import Vue
  from 'vue';

import Podcasts
  from '~/components/Stations/Podcasts.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(Podcasts, {
        props: props
      });
    }
  });
}
