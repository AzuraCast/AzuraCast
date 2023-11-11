#!/bin/bash
set -e
set -x

ARCHITECTURE=amd64
if [[ "$(uname -m)" = "aarch64" ]]; then
    ARCHITECTURE=arm64
fi

wget -O /tmp/sftpgo.deb "https://github.com/drakkan/sftpgo/releases/download/v2.5.5/sftpgo_2.5.5-1_${ARCHITECTURE}.deb"
dpkg -i /tmp/sftpgo.deb
rm -f /tmp/sftpgo.deb

mkdir -p /var/azuracast/sftpgo/persist /var/azuracast/sftpgo/backups

cp /bd_build/web/sftpgo/sftpgo.json /var/azuracast/sftpgo/sftpgo.json

touch /var/azuracast/sftpgo/sftpgo.db
chown -R azuracast:azuracast /var/azuracast/sftpgo
