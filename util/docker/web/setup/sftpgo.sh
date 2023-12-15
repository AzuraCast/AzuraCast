#!/bin/bash
set -e
set -x

mkdir -p /var/azuracast/sftpgo/persist \
  /var/azuracast/sftpgo/backups \
  /var/azuracast/sftpgo/env.d

cp /bd_build/web/sftpgo/sftpgo.json /var/azuracast/sftpgo/sftpgo.json

touch /var/azuracast/sftpgo/sftpgo.db
chown -R azuracast:azuracast /var/azuracast/sftpgo
