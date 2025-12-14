#!/bin/bash
set -e
set -x

export CENTRIFUGO_VERSION=6.5.2

mkdir -p /tmp/centrifugo
cd /tmp/centrifugo

# Per-architecture LS installs
ARCHITECTURE=amd64
if [[ "$(uname -m)" = "aarch64" ]]; then
    ARCHITECTURE=arm64
fi

wget -O centrifugo.tar.gz "https://github.com/centrifugal/centrifugo/releases/download/v${CENTRIFUGO_VERSION}/centrifugo_${CENTRIFUGO_VERSION}_linux_${ARCHITECTURE}.tar.gz"

tar -xzvf centrifugo.tar.gz

mv ./centrifugo /usr/local/bin/centrifugo

cd /tmp
rm -rf /tmp/centrifugo

chmod a+x /usr/local/bin/centrifugo

mkdir -p /var/azuracast/centrifugo
cp /bd_build/web/centrifugo/config.toml.tmpl /var/azuracast/centrifugo/config.toml.tmpl
