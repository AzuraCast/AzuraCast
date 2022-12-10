import Vue from 'vue';
import InlinePlayer from '~/components/InlinePlayer.vue';
import pinia from '../vendor/pinia';

document.addEventListener('DOMContentLoaded', function () {
    

    let inlinePlayer = new Vue({
        el: '#radio-player-controls',
        render: createElement => createElement(InlinePlayer),
        pinia
    });
});
