#!/bin/bash
set -e
set -x

# Build Icecast from source
apt-get install -q -y --no-install-recommends \
   build-essential libxml2 libxslt1-dev libvorbis-dev libssl-dev libcurl4-openssl-dev openssl

mkdir -p /bd_build/stations/icecast_build
cd /bd_build/stations/icecast_build

git clone https://github.com/karlheyes/icecast-kh.git .
git checkout 712ff595a2fa0550f543d8c2597766cfb5ce7ec2

./configure
make
make install

# Remove build tools
apt-get remove --purge -y build-essential libssl-dev libcurl4-openssl-dev

# Copy AzuraCast Icecast customizations
mkdir -p /bd_build/stations/icecast_customizations
cd /bd_build/stations/icecast_customizations

git clone https://github.com/AzuraCast/icecast-kh-custom-files.git .

cp -r web/* /usr/local/share/icecast/web
