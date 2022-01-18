#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

# Get acme.sh ACME client source
mkdir /src
git -C /src clone https://github.com/acmesh-official/acme.sh.git
cd /src/acme.sh

# Install acme.sh in /app
./acme.sh --install \
  --nocron \
  --auto-upgrade 0 \
  --home /usr/local/acme.sh \
  --config-home /etc/acme.sh/default

# Make house cleaning
cd /
rm -rf /src
