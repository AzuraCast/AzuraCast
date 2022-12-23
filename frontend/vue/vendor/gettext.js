import translations from "../../../translations/translations.json";
import {createGettext} from "vue3-gettext";
import {useAzuraCast} from "~/vendor/azuracast";

const {locale} = useAzuraCast();

const gettext = createGettext({
    defaultLanguage: locale,
    translations: translations,
    silent: true
});

export function useTranslate() {
    return gettext;
}

export function installTranslate(vueApp) {
    if (typeof locale !== 'undefined') {
        vueApp.config.language = locale;
    }

    vueApp.use(gettext);
}
