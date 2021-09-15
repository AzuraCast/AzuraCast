import '~/base.js';
import '~/vendor/bootstrapVue.js';
import '~/vendor/fancybox.js';
import '~/vendor/luxon.js';

import Vue
  from 'vue';

import '~/pages/InlinePlayer.js';

import Media
  from '~/components/Stations/Media.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(Media, {
        props: props
      });
    }
  });
}
