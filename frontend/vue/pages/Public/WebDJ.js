import '~/base.js';
import '~/vendor/bootstrapVue.js';

import Vue
  from 'vue';

import WebDJ
  from '~/components/Public/WebDJ.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(WebDJ, {
        props: props
      });
    }
  });
}
