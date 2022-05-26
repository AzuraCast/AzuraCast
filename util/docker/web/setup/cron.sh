#!/bin/bash
set -e
set -x

install_without_postinst() {
    local PACKAGE
    PACKAGE=$1

    mkdir -p /tmp/install_$PACKAGE
    cd /tmp/install_$PACKAGE

    apt-get download $PACKAGE
    dpkg --unpack $PACKAGE*.deb
    rm -f /var/lib/dpkg/info/$PACKAGE.postinst
    dpkg --configure $PACKAGE

    apt-get install -yf #To fix dependencies

    cd /
    rm -rf /tmp/install_$PACKAGE
}

install_without_postinst cron

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

cp -r /bd_build/web/cron/. /etc/cron.d/
chmod -R 600 /etc/cron.d/*
