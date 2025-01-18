#!/bin/bash
set -e
set -x

# Packages required by Liquidsoap
apt-get install -y --no-install-recommends \
    ffmpeg bubblewrap \
    libao4 libfaad2 libfdk-aac2 libgd3 liblo7 libmad0 libmagic1 libportaudio2 \
    libsdl2-image-2.0-0 libsdl2-ttf-2.0-0 libsoundtouch1 libxpm4 libpulse0 \
    libsamplerate0 libtag1v5 libsrt1.5-openssl liblilv-0-0

# Audio Post-processing
apt-get install -y --no-install-recommends ladspa-sdk

# Per-architecture LS installs
ARCHITECTURE=amd64
if [[ "$(uname -m)" = "aarch64" ]]; then
    ARCHITECTURE=arm64
fi

# Temporarily use 2.3.0 until upstream RRs are fixed
# wget -O /tmp/liquidsoap.deb "https://github.com/savonet/liquidsoap-release-assets/releases/download/rolling-release-v2.3.x/liquidsoap-c0cd13a_2.3.1-debian-bookworm-1_${ARCHITECTURE}.deb"
wget -O /tmp/liquidsoap.deb "https://github.com/savonet/liquidsoap/releases/download/v2.3.0/liquidsoap_2.3.0-debian-bookworm-1_${ARCHITECTURE}.deb"

dpkg -i /tmp/liquidsoap.deb
apt-get install -y -f --no-install-recommends
rm -f /tmp/liquidsoap.deb
ln -s /usr/bin/liquidsoap /usr/local/bin/liquidsoap

# Add temp directories for caching.
mkdir -p /tmp/liquidsoap_cache
chown -R azuracast:azuracast /tmp/liquidsoap_cache

mkdir -p /var/azuracast/www_tmp/liquidsoap_cache
chown -R azuracast:azuracast /var/azuracast/www_tmp/liquidsoap_cache

# Pre-warm the initial LS opcode cache.
export LIQ_CACHE_SYSTEM_DIR=/tmp/liquidsoap_cache
export LIQ_CACHE_USER_DIR=/var/azuracast/www_tmp/liquidsoap_cache

gosu azuracast liquidsoap --cache-stdlib

# Add Common AzuraCast Functions
mkdir -p /var/azuracast/liquidsoap
cp /bd_build/stations/liquidsoap/* /var/azuracast/liquidsoap
chown -R azuracast:azuracast /var/azuracast/liquidsoap
