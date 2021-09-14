import {
  DateTime,
  Settings
} from 'luxon';

document.addEventListener('DOMContentLoaded', function () {
  Settings.defaultLocale = App.locale_with_dashes;
});

export default DateTime;
