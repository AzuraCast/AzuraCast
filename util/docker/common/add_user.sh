#!/bin/bash
set -e
set -x

apt-get install -y --no-install-recommends sudo

# Workaround for sudo errors in containers, see: https://github.com/sudo-project/sudo/issues/42
echo "Set disable_coredump false" >> /etc/sudo.conf

adduser --home /var/azuracast --disabled-password --gecos "" azuracast

usermod -aG www-data azuracast

mkdir -p /var/azuracast/www /var/azuracast/stations /var/azuracast/www_tmp \
  /var/azuracast/backups \
  /var/azuracast/dbip \
  /var/azuracast/storage/uploads \
  /var/azuracast/storage/shoutcast2 \
  /var/azuracast/storage/stereo_tool \
  /var/azuracast/storage/geoip \
  /var/azuracast/storage/sftpgo \
  /var/azuracast/storage/acme

chown -R azuracast:azuracast /var/azuracast
chmod -R 777 /var/azuracast/www_tmp

echo 'azuracast ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers
