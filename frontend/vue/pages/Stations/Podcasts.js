import '~/init/base.js';
import '~/init/bootstrapVue.js';
import '~/init/fancybox.js';

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
