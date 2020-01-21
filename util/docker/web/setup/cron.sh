#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

$minimal_apt_get_install cron

service cron stop

chmod 600 /etc/crontab

# Fix cron issues in 0.9.19, see also #345: https://github.com/phusion/baseimage-docker/issues/345
sed -i 's/^\s*session\s\+required\s\+pam_loginuid.so/# &/' /etc/pam.d/cron

## Remove useless cron entries.
# Checks for lost+found and scans for mtab.
rm -f /etc/cron.daily/standard
rm -f /etc/cron.daily/upstart
rm -f /etc/cron.daily/dpkg
rm -f /etc/cron.daily/password
rm -f /etc/cron.weekly/fstrim

cp -r /bd_build/cron/. /etc/cron.d/
chmod -R 600 /etc/cron.d/*