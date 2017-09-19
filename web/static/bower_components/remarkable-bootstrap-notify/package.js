Package.describe({
  git: 'git://github.com/mouse0270/bootstrap-notify.git',
  name: 'mouse0270:bootstrap-notify',
  summary: 'Turns standard Bootstrap alerts into "Growl-like" notifications',
  version: '3.1.3',
});

Package.onUse(function (api) {
  api.versionsFrom('1.0');
  api.use('jquery', 'client');
  api.addFiles('bootstrap-notify.js', 'client');
});

Package.onTest(function (api) {
  api.use('mouse0270:bootstrap-notify', 'client');
  api.use('tinytest', 'client');

  api.addFiles('test_meteor.js', 'client');
});
