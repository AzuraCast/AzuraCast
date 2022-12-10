import translations from "../../../translations/translations.json";
import {createGettext} from "vue3-gettext";

export default createGettext({
    defaultLanguage: 'en_US',
    translations: translations,
    silent: true
});
