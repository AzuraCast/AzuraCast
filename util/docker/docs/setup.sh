#!/bin/bash
set -e
set -x

export DEBIAN_FRONTEND=noninteractive

mkdir -p /tmp/docs
cd /tmp/docs

# Cached commit: ff7abc40a61281e2474c724cc6f578dd382e7320
git clone https://github.com/AzuraCast/azuracast.com.git .
cd builtin
bash build.sh

mkdir -p /var/azuracast/docs
cp -TR /tmp/docs/builtin/dist/ /var/azuracast/docs/

rm -rf /tmp/docs
