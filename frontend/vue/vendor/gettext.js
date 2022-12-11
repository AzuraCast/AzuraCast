import translations from "../../../translations/translations.json";
import {createGettext} from "vue3-gettext";

export default createGettext({
    defaultLanguage: App.locale,
    translations: translations,
    silent: true
});
