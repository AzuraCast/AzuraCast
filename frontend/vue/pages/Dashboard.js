import '~/init/base.js';
import '~/init/bootstrapVue.js';
import '~/init/inlinePlayer.js';

import Vue
  from 'vue';

import Dashboard
  from '~/components/Dashboard.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: createElement => createElement(Dashboard, { props: props })
  });
}
