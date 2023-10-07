#!/bin/bash
set -e
set -x

# Group up several package installations here to reduce overall build time
curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
NODE_MAJOR=20
echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list

add-apt-repository -y ppa:chris-needham/ppa
add-apt-repository -y ppa:ondrej/php
apt-get update

apt-get install -y --no-install-recommends \
  audiowaveform=1.9.1-1jammy1 \
  nginx nginx-common openssl \
  nodejs \
  tmpreaper \
  zstd \
  netbase
