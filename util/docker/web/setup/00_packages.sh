#!/bin/bash
set -e
set -x

# Group up several package installations here to reduce overall build time

add-apt-repository -y ppa:chris-needham/ppa
add-apt-repository -y ppa:ondrej/php
apt-get update

apt-get install -y --no-install-recommends \
  audiowaveform=1.9.0-1jammy1 \
  nginx nginx-common openssl \
  tmpreaper \
  zstd \
  netbase
