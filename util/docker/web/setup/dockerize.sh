#!/bin/bash
set -e
set -x

export DOCKERIZE_VERSION=0.9.3

mkdir -p /tmp/dockerize
cd /tmp/dockerize

# Per-architecture LS installs
ARCHITECTURE=amd64
if [[ "$(uname -m)" = "aarch64" ]]; then
    ARCHITECTURE=arm64
fi

wget -O dockerize.tar.gz "https://github.com/jwilder/dockerize/releases/download/v${DOCKERIZE_VERSION}/dockerize-linux-${ARCHITECTURE}-v${DOCKERIZE_VERSION}.tar.gz"

tar -xzvf dockerize.tar.gz

mv ./dockerize /usr/local/bin/dockerize

cd /tmp
rm -rf /tmp/dockerize

chmod a+x /usr/local/bin/dockerize
