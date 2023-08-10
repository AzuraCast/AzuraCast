import {createGettext, Language} from "vue3-gettext";
import {App} from "vue";
import {useAzuraCast} from "~/vendor/azuracast.ts";

let gettext;

export function useTranslate(): Language {
    return gettext;
}

export function installTranslate(vueApp: App): void {
    const {locale} = useAzuraCast();

    gettext = createGettext({
        defaultLanguage: locale,
        translations: {},
        silent: true
    });

    const translations = import.meta.glob('../../../translations/**/translations.json', {as: 'json'});
    const localePath = '../../../translations/' + locale + '.UTF-8/translations.json';

    if (localePath in translations) {
        translations[localePath]().then((data) => {
            gettext.translations = data;
        });
    }

    vueApp.use(gettext);
}
