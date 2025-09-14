import {createGettext, Language} from "vue3-gettext";
import {App} from "vue";
import {useAzuraCast} from "~/vendor/azuracast.ts";

let gettext: Language;

export function useTranslate(): Language {
    return gettext;
}

export async function installTranslate(vueApp: App): Promise<void> {
    const {locale} = useAzuraCast();

    const translations = import.meta.glob('../../translations/**/translations.json', {query: '?json'});
    const localePath = '../../translations/' + locale + '.UTF-8/translations.json';

    gettext = createGettext({
        defaultLanguage: locale,
        // @ts-expect-error TS can't analyze the Vite meta
        translations: (localePath in translations) ?
            await translations[localePath]()
            : {},
        silent: true
    });

    vueApp.use(gettext);
}
