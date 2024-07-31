#!/bin/bash
set -e
set -x

# Packages required by Liquidsoap
apt-get install -y --no-install-recommends \
    libao4 libfaad2 libfdk-aac2 libgd3 liblo7 libmad0 libmagic1 libportaudio2 \
    libsdl2-image-2.0-0 libsdl2-ttf-2.0-0 libsoundtouch1 libxpm4 \
    libasound2 libavcodec60 libavdevice60 libavfilter9 libavformat60 libavutil58 \
    libpulse0 libsamplerate0 libswresample4 libswscale7 libtag1v5 \
    libsrt1.5-openssl bubblewrap ffmpeg liblilv-0-0 libjemalloc2 libpcre3

# Audio Post-processing
apt-get install -y --no-install-recommends ladspa-sdk

# Per-architecture LS installs
ARCHITECTURE=amd64
if [[ "$(uname -m)" = "aarch64" ]]; then
    ARCHITECTURE=arm64
fi

wget -O /tmp/liquidsoap.deb "https://github.com/savonet/liquidsoap-release-assets/releases/download/rolling-release-v2.3.x/liquidsoap-10453cf_2.3.0-debian-bookworm-1_${ARCHITECTURE}.deb"
# wget -O /tmp/liquidsoap.deb "https://github.com/savonet/liquidsoap/releases/download/v2.2.5/liquidsoap_2.2.5-debian-bookworm-1_${ARCHITECTURE}.deb"

dpkg -i /tmp/liquidsoap.deb
apt-get install -y -f --no-install-recommends
rm -f /tmp/liquidsoap.deb
ln -s /usr/bin/liquidsoap /usr/local/bin/liquidsoap
