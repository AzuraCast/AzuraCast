#!/bin/bash
set -e
set -x

export SUPERCRONIC_VERSION=0.2.41

# Per-architecture LS installs
ARCHITECTURE=amd64
if [[ "$(uname -m)" = "aarch64" ]]; then
    ARCHITECTURE=arm64
fi

wget -O /usr/local/bin/supercronic "https://github.com/aptible/supercronic/releases/download/v${SUPERCRONIC_VERSION}/supercronic-linux-${ARCHITECTURE}"
chmod a+x /usr/local/bin/supercronic
