#!/bin/bash
set -e
set -x

export DEBIAN_FRONTEND=noninteractive

mkdir -p /tmp/docs
cd /tmp/docs

# Updated 2023-10-10
git clone https://github.com/AzuraCast/azuracast.com.git .
cd builtin
bash build.sh

mkdir -p /var/azuracast/docs
cp -TR /tmp/docs/builtin/dist/ /var/azuracast/docs/

rm -rf /tmp/docs
