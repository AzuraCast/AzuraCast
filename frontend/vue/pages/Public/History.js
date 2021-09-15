import '~/base.js';
import '~/vendor/luxon.js';

import Vue
  from 'vue';

import History
  from '~/components/Public/History.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(History, {
        props: props
      });
    }
  });
}
