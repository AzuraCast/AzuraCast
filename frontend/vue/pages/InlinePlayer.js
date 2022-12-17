import {createApp} from 'vue';
import InlinePlayer from '~/components/InlinePlayer.vue';
import installPinia from '../vendor/pinia';
import gettext from "../vendor/gettext";

document.addEventListener('DOMContentLoaded', function () {
    const inlineApp = createApp(InlinePlayer);

    /* Gettext */
    if (typeof App.locale !== 'undefined') {
        inlineApp.config.language = App.locale;
    }

    inlineApp.use(gettext);

    /* Pinia */
    installPinia(inlineApp);

    inlineApp.mount('#radio-player-controls');
});
