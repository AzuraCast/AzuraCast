import '~/init/base.js';
import '~/init/bootstrapVue.js';

import Vue
  from 'vue';

import StorageLocations
  from '~/components/Admin/StorageLocations.vue';

export default function (el, props) {
  return new Vue({
    el: el,
    render: (createElement) => {
      return createElement(StorageLocations, {
        props: props
      });
    }
  });
}
