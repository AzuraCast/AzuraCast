import '~/init/base.js';
import '~/init/bootstrapVue.js';
import '~/init/inlinePlayer.js';

import Vue
  from 'vue';

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
