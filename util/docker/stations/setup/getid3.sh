#!/bin/bash
set -e
set -x

# Audio library dependencies used by Php-Getid3
apt-get install -q -y --no-install-recommends \
    vorbis-tools flac
