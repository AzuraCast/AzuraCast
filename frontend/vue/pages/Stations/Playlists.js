import '~/init/base.js';
import '~/init/bootstrapVue.js';

import Vue
  from 'vue';

import Playlists
  from '~/components/Stations/Playlists.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(Playlists, {
        props: props
      });
    }
  });
}
