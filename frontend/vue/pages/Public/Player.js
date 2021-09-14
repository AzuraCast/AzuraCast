import '~/base.js';
import '~/store.js';

import Vue
  from 'vue';

import Player
  from '~/components/Public/Player.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(Player, {
        props: props
      });
    }
  });
}
