import '~/init/base.js';
import '~/init/bootstrapVue.js';
import '~/init/inlinePlayer.js';

import Vue
  from 'vue';

import Profile
  from '~/components/Stations/Profile.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(Profile, {
        props: props
      });
    }
  });
}
