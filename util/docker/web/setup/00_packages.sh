#!/bin/bash
set -e
set -x

# Group up several package installations here to reduce overall build time

add-apt-repository -y ppa:chris-needham/ppa
add-apt-repository -y ppa:sftpgo/sftpgo
add-apt-repository -y ppa:ondrej/php

echo "deb [trusted=yes] https://apt.fury.io/meilisearch/ /" | sudo tee /etc/apt/sources.list.d/fury.list

apt-get update

apt-get install -y --no-install-recommends \
  audiowaveform \
  nginx nginx-common openssl \
  sftpgo \
  tmpreaper \
  zstd \
  netbase \
  meilisearch-http
