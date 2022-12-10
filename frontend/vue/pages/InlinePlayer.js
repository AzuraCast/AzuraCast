import Vue from 'vue';
import InlinePlayer from '~/components/InlinePlayer.vue';
import {createPinia, PiniaVuePlugin} from 'pinia';

Vue.use(PiniaVuePlugin);
const pinia = createPinia();

document.addEventListener('DOMContentLoaded', function () {
    let inlinePlayer = new Vue({
        el: '#radio-player-controls',
        render: createElement => createElement(InlinePlayer),
        pinia
    });
});
