import {useAzuraCast} from "~/vendor/azuracast.ts";

import {
    cs,
    de,
    el,
    enUS,
    es,
    fr,
    it,
    ja,
    ko,
    Locale,
    nb,
    nl,
    pl,
    pt,
    ptBR,
    ru,
    sv,
    tr,
    uk,
    zhCN
} from 'date-fns/locale';
import {isString} from "es-toolkit";

const localeLookup: Record<string, Locale> = {
    'en_US': enUS,
    'cs_CZ': cs,
    'nl_NL': nl,
    'fr_FR': fr,
    'de_DE': de,
    'el_GR': el,
    'it_IT': it,
    'ja_JP': ja,
    'ko_KR': ko,
    'nb_NO': nb,
    'pl_PL': pl,
    'pt_PT': pt,
    'pt_BR': ptBR,
    'ru_RU': ru,
    'zh_CN': zhCN,
    'es_ES': es,
    'sv_SE': sv,
    'tr_TR': tr,
    'uk_UA': uk
};

export const getDateFnLocale = (localeName?: string): Locale => {
    if (!isString(localeName)) {
        const {locale} = useAzuraCast();
        localeName = locale;
    }

    if (localeName in localeLookup) {
        return localeLookup[localeName];
    }

    return enUS;
};
