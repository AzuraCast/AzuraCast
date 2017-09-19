// package metadata file for Meteor.js
var packageName = 'digimet:flowjs';
var where = 'client'; // where to install: 'client' or 'server'. For both, pass nothing.
var version = '2.9.0';
var summary = 'Flow.js html5 file upload extension';
var gitLink = 'https://github.com/flowjs/flow.js.git';
var documentationFile = 'README.md';

// Meta-data
Package.describe({
  name: packageName,
  version: version,
  summary: summary,
  git: gitLink,
  documentation: documentationFile
});

Package.onUse(function(api) {
  api.versionsFrom(['METEOR@0.9.0', 'METEOR@1.0']); // Meteor versions

  
  api.addFiles('./dist/flow.js', where); // Files in use

});