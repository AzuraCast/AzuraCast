#!/usr/bin/env bash

rm -rf /data
mkdir /data

cd /data

ln -s /var/azuracast/www/web/static/gruntfile.js    /data/gruntfile.js
ln -s /var/azuracast/www/web/static/package.json    /data/package.json
ln -s /var/azuracast/www/web/static/assets.json     /data/assets.json
ln -s /var/azuracast/www/web/static/css             /data/css
ln -s /var/azuracast/www/web/static/js              /data/js
ln -s /var/azuracast/www/web/static/less            /data/less
ln -s /var/azuracast/www/web/static/vendors         /data/vendors

npm install --loglevel warn

bash