import {createGettext, Language} from "vue3-gettext";
import {App} from "vue";
import {useAzuraCast} from "~/vendor/azuracast.ts";

let gettext;

export function useTranslate(): Language {
    return gettext;
}

export async function installTranslate(vueApp: App): Promise<void> {
    const {locale} = useAzuraCast();

    const translationsRaw = import.meta.glob('../../../translations/**/translations.json', {
        as: 'raw',
    });
    const localePath = '../../../translations/' + locale + '.UTF-8/translations.json';

    let translations = {};
    if (localePath in translationsRaw) {
        translations = await JSON.parse(
            await translationsRaw[localePath]()
        );
    }

    gettext = createGettext({
        defaultLanguage: locale,
        translations: translations,
        silent: true
    });

    vueApp.use(gettext);
}
