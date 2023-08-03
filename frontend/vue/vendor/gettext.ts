import {createGettext, Language} from "vue3-gettext";
import {useAzuraCast} from "~/vendor/azuracast";
import axios from "axios";
import {App} from "vue";

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

export function useTranslate(): Language {
    return gettext;
}

export function installTranslate(vueApp: App): void {
    if (typeof locale !== 'undefined') {
        vueApp.config.language = locale;
    }

    vueApp.use(gettext);
}
