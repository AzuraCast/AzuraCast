#!/bin/bash
set -e
set -x

# Build Icecast from source
apt-get install -q -y --no-install-recommends \
   build-essential libxml2 libxslt1-dev libvorbis-dev libssl-dev libcurl4-openssl-dev openssl

mkdir -p /bd_build/stations/icecast
cd /bd_build/stations/icecast

curl -fsSL -o icecast.tar.gz https://github.com/AzuraCast/icecast-kh-ac/archive/refs/tags/2.4.0-kh15-ac2.tar.gz
tar -xzvf icecast.tar.gz --strip-components=1
./configure
make
make install

apt-get remove --purge -y build-essential libssl-dev libcurl4-openssl-dev
