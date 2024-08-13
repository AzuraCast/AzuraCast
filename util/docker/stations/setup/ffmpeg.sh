#!/bin/bash
set -e
set -x

# Set up deb-multimedia, which has newer versions of ffmpeg and other multimedia libraries than Debian Bookworm.

# Add the trusted keyring file.
mkdir -p /tmp/ffmpeg-keyring
cd /tmp/ffmpeg-keyring

wget https://www.deb-multimedia.org/pool/main/d/deb-multimedia-keyring/deb-multimedia-keyring_2016.8.1_all.deb
dpkg -i ./deb-multimedia-keyring_2016.8.1_all.deb

cd /tmp
rm -rf /tmp/ffmpeg-keyring

# Configure the APT repos.
echo "Types: deb
URIs: https://www.deb-multimedia.org
Suites: stable
Components: main non-free
Signed-By: /etc/apt/trusted.gpg.d/deb-multimedia-keyring.gpg" >> /etc/apt/sources.list.d/deb-multimedia.sources

echo "Package: *
Pin: origin www.deb-multimedia.org
Pin-Priority: 900" >> /etc/apt/preferences.d/99deb-multimedia

apt-get update

# Update any existing packages that were installed with older versions.
apt-get dist-upgrade -y

apt-get install -y --no-install-recommends ffmpeg
