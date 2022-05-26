#!/bin/bash
set -e
set -x

# Only install Icecast deps (Icecast is handled by another container).
apt-get install -y --no-install-recommends libxml2 libxslt1-dev libvorbis-dev

# SSL self-signed cert generation
apt-get install -y --no-install-recommends openssl
