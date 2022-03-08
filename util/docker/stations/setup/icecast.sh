#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

# Only install Icecast deps (Icecast is handled by another container).
$minimal_apt_get_install libxml2 libxslt1-dev libvorbis-dev

# SSL self-signed cert generation
$minimal_apt_get_install openssl
