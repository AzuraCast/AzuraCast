#!/bin/bash
set -e
set -x

mkdir -p /tmp/master_me
cd /tmp/master_me

# Per-architecture LS installs
ARCHITECTURE=x86_64
if [[ "$(uname -m)" = "aarch64" ]]; then
    ARCHITECTURE=arm64
fi

wget -O master_me.tar.xz "https://github.com/trummerschlunk/master_me/releases/download/1.2.0/master_me-1.2.0-linux-${ARCHITECTURE}.tar.xz"

tar -xvf master_me.tar.xz --strip-components=1

mkdir -p /usr/lib/ladspa
mkdir -p /usr/lib/lv2

mv ./master_me-easy-presets.lv2 /usr/lib/lv2
mv ./master_me.lv2 /usr/lib/lv2
mv ./master_me-ladspa.so /usr/lib/ladspa/master_me.so

cd /tmp
rm -rf /tmp/master_me
