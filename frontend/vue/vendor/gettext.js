import translations from "../../../translations/translations.json";
import {createGettext} from "vue3-gettext";
import useAzuraCast from "~/vendor/azuracast";

const {locale} = useAzuraCast();

export default createGettext({
    defaultLanguage: locale,
    translations: translations,
    silent: true
});
