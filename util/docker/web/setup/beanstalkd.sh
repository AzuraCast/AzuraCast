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

apt-get install -y --no-install-recommends netbase

install_without_postinst beanstalkd
