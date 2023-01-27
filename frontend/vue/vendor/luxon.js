import {Settings} from 'luxon';
import {useAzuraCast} from "~/vendor/azuracast";

const {localeWithDashes} = useAzuraCast();

document.addEventListener('DOMContentLoaded', function () {
    Settings.defaultLocale = localeWithDashes;
});
