#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

$minimal_apt_get_install sudo

# Workaround for sudo errors in containers, see: https://github.com/sudo-project/sudo/issues/42
echo "Set disable_coredump false" >> /etc/sudo.conf

adduser --home /var/azuracast --disabled-password --gecos "" azuracast

usermod -aG docker_env azuracast
usermod -aG www-data azuracast

mkdir -p /var/azuracast/www /var/azuracast/backups /var/azuracast/www_tmp /var/azuracast/geoip /var/azuracast/dbip

chown -R azuracast:azuracast /var/azuracast
chmod -R 777 /var/azuracast/www_tmp

echo 'azuracast ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers