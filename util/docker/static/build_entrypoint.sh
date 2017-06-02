#!/usr/bin/env bash

cd /data

rm -rf /data/gruntfile.js
rm -rf /data/package.json

ln -s /var/azuracast/www/web/static/gruntfile.js /data/gruntfile.js
ln -s /var/azuracast/www/web/static/package.json /data/package.json

npm install --loglevel warn

bash