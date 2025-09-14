#!/bin/bash
set -e
set -x

# Per-architecture LS installs
ARCHITECTURE=amd64
if [[ "$(uname -m)" = "aarch64" ]]; then
    ARCHITECTURE=arm64
fi

apt-get install -y --no-install-recommends \
  libid3tag0 libboost-program-options1.83.0 libboost-filesystem1.83.0 libboost-regex1.83.0

wget -O /tmp/audiowaveform.deb "https://github.com/bbc/audiowaveform/releases/download/1.10.2/audiowaveform_1.10.2-1-13_${ARCHITECTURE}.deb"

dpkg -i /tmp/audiowaveform.deb
apt-get install -y -f --no-install-recommends
rm -f /tmp/audiowaveform.deb
