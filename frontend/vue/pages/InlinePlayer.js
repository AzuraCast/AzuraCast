import Vue
  from 'vue';

import store
  from '~/store';

import InlinePlayer
  from '~/components/InlinePlayer.vue';

document.addEventListener('DOMContentLoaded', function () {
  let inlinePlayer = new Vue({
    el: '#radio-player-controls',
    store: store,
    render: createElement => createElement(InlinePlayer)
  });
});
