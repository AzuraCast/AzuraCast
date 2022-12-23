import {createApp} from 'vue';
import InlinePlayer from '~/components/InlinePlayer.vue';
import {installPinia} from '~/vendor/pinia';
import {installTranslate} from "~/vendor/gettext";

document.addEventListener('DOMContentLoaded', function () {
    const inlineApp = createApp(InlinePlayer);

    /* Gettext */
    installTranslate(inlineApp);

    /* Pinia */
    installPinia(inlineApp);

    inlineApp.mount('#radio-player-controls');
});
