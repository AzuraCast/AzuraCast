#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

mkdir -p /var/azuracast/sftpgo/persist /var/azuracast/sftpgo/backups

cp /bd_build/sftpgo/sftpgo.json /var/azuracast/sftpgo/sftpgo.json

touch /var/azuracast/sftpgo/sftpgo.db
chown -R azuracast:azuracast /var/azuracast/sftpgo