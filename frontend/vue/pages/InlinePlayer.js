import {createApp} from 'vue';
import InlinePlayer from '~/components/InlinePlayer.vue';
import installPinia from '../vendor/pinia';
import gettext from "../vendor/gettext";
import useAzuraCast from "~/vendor/azuracast";

document.addEventListener('DOMContentLoaded', function () {
    const inlineApp = createApp(InlinePlayer);

    /* Gettext */
    const {locale} = useAzuraCast();

    if (typeof locale !== 'undefined') {
        inlineApp.config.language = locale;
    }

    inlineApp.use(gettext);

    /* Pinia */
    installPinia(inlineApp);

    inlineApp.mount('#radio-player-controls');
});
