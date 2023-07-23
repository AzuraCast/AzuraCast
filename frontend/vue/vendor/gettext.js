import {createGettext} from "vue3-gettext";
import {useAzuraCast} from "~/vendor/azuracast";
import axios from "axios";

const {locale, localePaths} = useAzuraCast();

const gettext = createGettext({
    defaultLanguage: locale,
    translations: {},
    silent: true
});

if (localePaths[locale] !== undefined) {
    axios.get(
        localePaths[locale]
    ).then(r => {
        gettext.translations = r.data;
    }).catch((e) => {
        console.error(e);
    });
}

export function useTranslate() {
    return gettext;
}

export function installTranslate(vueApp) {
    if (typeof locale !== 'undefined') {
        vueApp.config.language = locale;
    }

    vueApp.use(gettext);
}
