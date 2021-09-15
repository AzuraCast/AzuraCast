import '~/base.js';
import '~/vendor/bootstrapVue.js';
import '~/vendor/chartjs.js';

import Vue
  from 'vue';

import '~/pages/InlinePlayer.js';

import Dashboard
  from '~/components/Dashboard.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: createElement => createElement(Dashboard, { props: props })
  });
}
