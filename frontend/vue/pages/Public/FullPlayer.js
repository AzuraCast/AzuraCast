import '~/base.js';
import '~/vendor/bootstrapVue.js';
import '~/store.js';
import '~/vendor/fancybox.js';
import '~/vendor/luxon.js';

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
