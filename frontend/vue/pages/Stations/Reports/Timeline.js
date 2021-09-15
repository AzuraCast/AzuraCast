import '~/base.js';
import '~/vendor/bootstrapVue.js';
import '~/vendor/luxon.js';

import Vue
  from 'vue';

import Timeline
  from '~/components/Stations/Reports/Timeline.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(Timeline, {
        props: props
      });
    }
  });
}
