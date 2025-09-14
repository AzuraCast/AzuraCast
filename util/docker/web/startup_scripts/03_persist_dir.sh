#!/bin/bash

export DEBIAN_FRONTEND=noninteractive

echo "Creating persist directories..."

mkdir -p \
  /var/azuracast/storage/acme \
  /var/azuracast/storage/geoip \
  /var/azuracast/storage/rsas \
  /var/azuracast/storage/sftpgo \
  /var/azuracast/storage/shoutcast2 \
  /var/azuracast/storage/stereo_tool \
  /var/azuracast/storage/uploads

chown azuracast:azuracast /var/azuracast/storage/* || true
