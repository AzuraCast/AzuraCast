import '~/base.js';
import '~/vendor/bootstrapVue.js';
import '~/vendor/fancybox.js';
import '~/vendor/luxon.js';

import Vue
  from 'vue';

import '~/pages/InlinePlayer.js';

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
