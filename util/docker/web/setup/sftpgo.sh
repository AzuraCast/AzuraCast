#!/bin/bash
set -e
set -x

# Per-architecture LS installs
ARCHITECTURE=amd64
if [[ "$(uname -m)" = "aarch64" ]]; then
    ARCHITECTURE=arm64
fi

wget -O /tmp/sftpgo.deb "https://github.com/drakkan/sftpgo/releases/download/v2.5.6/sftpgo_2.5.6-1_${ARCHITECTURE}.deb"

dpkg -i /tmp/sftpgo.deb
apt-get install -y -f --no-install-recommends
rm -f /tmp/sftpgo.deb

mkdir -p /var/azuracast/sftpgo/persist \
  /var/azuracast/sftpgo/backups \
  /var/azuracast/sftpgo/env.d

cp /bd_build/web/sftpgo/sftpgo.json /var/azuracast/sftpgo/sftpgo.json

touch /var/azuracast/sftpgo/sftpgo.db
chown -R azuracast:azuracast /var/azuracast/sftpgo

# Create sftpgo temp dir
mkdir -p /tmp/sftpgo_temp
touch /tmp/sftpgo_temp/.tmpreaper
chmod -R 777 /tmp/sftpgo_temp
