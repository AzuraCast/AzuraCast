import {Settings} from 'luxon';

document.addEventListener('DOMContentLoaded', function () {
  Settings.defaultLocale = document.body.App.locale_with_dashes;

  Settings.defaultZoneName = 'UTC';
});
