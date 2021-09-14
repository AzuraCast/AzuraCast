import '~/base.js';
import '~/vendor/bootstrapVue.js';

import Vue
  from 'vue';

import OnDemand
  from '~/components/Public/OnDemand.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(OnDemand, {
        props: props
      });
    }
  });
}
