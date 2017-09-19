#!/usr/bin/env bash

cd /data

ln -s /src/gruntfile.js    /data/gruntfile.js
ln -s /src/assets.json     /data/assets.json
ln -s /src/css             /data/css
ln -s /src/js              /data/js
ln -s /src/less            /data/less
ln -s /src/vendors         /data/vendors
ln -s /src/bower_components /data/bower_components
ln -s /src/bower.json      /data/bower.json

bash