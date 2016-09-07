#!/usr/bin/env bash

# Install app dependencies
apt-get -q -y install nodejs npm

# Set Node.js bin alias
ln -s /usr/bin/nodejs /usr/bin/node

mkdir -p /var/azuracast/build
chown -R azuracast:www-data /var/azuracast/build

ln -s $www_base/web/static/gruntfile.js /var/azuracast/build/gruntfile.js
ln -s $www_base/web/static/package.json /var/azuracast/build/package.json

cd /var/azuracast/build
npm install --loglevel warn
npm install -g bower --loglevel warn
npm install -g grunt --loglevel warn