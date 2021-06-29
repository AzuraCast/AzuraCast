#!/bin/bash
set -e
source /bd_build_base/buildconfig
set -x

# Only install Liquidsoap deps (Liquidsoap build is handled by another container).
$minimal_apt_get_install libfaad-dev libfdk-aac-dev libflac-dev libmad0-dev libmp3lame-dev libogg-dev \
    libopus-dev libpcre3-dev libtag1-dev libsamplerate0-dev libavcodec-dev libavfilter-dev \
    libavformat-dev libavresample-dev libavutil-dev libavdevice-dev libpostproc-dev libswresample-dev \
    ladspa-sdk multimedia-audio-plugins swh-plugins tap-plugins lsp-plugins-ladspa
