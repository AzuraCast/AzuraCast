#!/usr/bin/env bash

apt-get update

apt-get -q -y install pwgen icecast2 liquidsoap

# Generate new passwords for Icecast

export icecast_pw_source=`pwgen 8`
export icecast_pw_relay=`pwgen 8`
export icecast_pw_admin=`pwgen 8`

sed -e 's/<source-password>hackme<\/source-password>/<source-password>'$icecast_pw_source'<\/source-password>/g' /etc/icecast2/icecast.xml
sed -e 's/<relay-password>hackme<\/relay-password>/<relay-password>'$icecast_pw_relay'<\/relay-password>/g' /etc/icecast2/icecast.xml
sed -e 's/<admin-password>hackme<\/admin-password>/<admin-password>'$icecast_pw_admin'<\/admin-password>/g' /etc/icecast2/icecast.xml