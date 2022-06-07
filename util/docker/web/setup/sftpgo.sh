#!/bin/bash
set -e
set -x

add-apt-repository -y ppa:sftpgo/sftpgo
apt-get update

apt-get install -y --no-install-recommends sftpgo

mkdir -p /var/azuracast/sftpgo/persist /var/azuracast/sftpgo/backups

cp /bd_build/web/sftpgo/sftpgo.json /var/azuracast/sftpgo/sftpgo.json

touch /var/azuracast/sftpgo/sftpgo.db
chown -R azuracast:azuracast /var/azuracast/sftpgo
