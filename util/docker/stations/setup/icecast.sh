#!/bin/bash
set -e
set -x

# Build Icecast from source
apt-get install -q -y --no-install-recommends \
   build-essential libxml2 libxslt1-dev libvorbis-dev libssl-dev libcurl4-openssl-dev openssl

mkdir -p /bd_build/stations/icecast_build
cd /bd_build/stations/icecast_build

# Get the latest release tag name for Icecast
tag_name=$(curl -s https://api.github.com/repos/karlheyes/icecast-kh/releases/latest | grep "tag_name" | cut -d '"' -f 4)

# Construct the release URL for Icecast
release_url="https://github.com/karlheyes/icecast-kh/archive/refs/tags/$tag_name.tar.gz"

# Download and extract the Icecast source code
curl -fsSL -o icecast.tar.gz "$release_url"
tar -xvzf icecast.tar.gz --strip-components=1

# git clone https://github.com/karlheyes/icecast-kh.git .
# git checkout 3b04a78133b7c4b8f879b55e83c139532976de87

# Build and install Icecast
./configure
make
make install

# Remove build tools
apt-get remove --purge -y build-essential libxslt1-dev libvorbis-dev libssl-dev libcurl4-openssl-dev

# Copy AzuraCast Icecast customizations
mkdir -p /bd_build/stations/icecast_customizations
cd /bd_build/stations/icecast_customizations

# git clone https://github.com/AzuraCast/icecast-kh-custom-files.git .

curl -fsSL https://github.com/AzuraCast/icecast-kh-custom-files/archive/refs/tags/2023-01-21.tar.gz \
  -o custom-files.tar.gz
tar -xvzf custom-files.tar.gz --strip-components=1

cp -r web/* /usr/local/share/icecast/web
