#!/bin/bash
set -e
set -x

# Per-architecture installs
ARCHITECTURE=amd64
if [[ "$(uname -m)" = "aarch64" ]]; then
    ARCHITECTURE=arm64
fi

cd /tmp
wget -O centrifugo.tar.gz "https://github.com/centrifugal/centrifugo/releases/download/v4.0.4/centrifugo_4.0.4_linux_${ARCHITECTURE}.tar.gz"

tar -xzvf centrifugo.tar.gz

cp centrifugo /usr/local/bin/centrifugo
chmod a+x /usr/local/bin/centrifugo

mkdir -p /var/azuracast/centrifugo
cp /bd_build/web/centrifugo/config.json /var/azuracast/centrifugo/config.json

