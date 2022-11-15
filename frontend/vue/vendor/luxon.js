import { Settings } from 'luxon';

document.addEventListener('DOMContentLoaded', function () {
  Settings.defaultLocale = App.locale_with_dashes;

  Settings.defaultZoneName = 'UTC';
});
