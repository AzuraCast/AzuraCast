import '~/init/base.js';
import '~/init/bootstrapVue.js';
import '~/init/store.js';
import '~/init/fancybox.js';

import Vue
  from 'vue';

import FullPlayer
  from '~/components/Public/FullPlayer.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(FullPlayer, {
        props: props
      });
    }
  });
}
