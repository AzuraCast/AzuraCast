#!/bin/bash
set -e
set -x

apt-get install -y --no-install-recommends sudo

# Workaround for sudo errors in containers, see: https://github.com/sudo-project/sudo/issues/42
echo "Set disable_coredump false" >> /etc/sudo.conf

adduser --home /var/azuracast --disabled-password --gecos "" azuracast

usermod -aG www-data azuracast

mkdir -p /var/azuracast/www /var/azuracast/stations /var/azuracast/servers/shoutcast2 \
  /var/azuracast/servers/stereo_tool /var/azuracast/backups /var/azuracast/www_tmp \
  /var/azuracast/uploads /var/azuracast/geoip /var/azuracast/dbip \
  /var/azuracast/acme

chown -R azuracast:azuracast /var/azuracast
chmod -R 777 /var/azuracast/www_tmp

echo 'azuracast ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers
