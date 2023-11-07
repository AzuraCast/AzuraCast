#!/bin/bash

export DEBIAN_FRONTEND=noninteractive

echo "Creating persist directories..."

mkdir -p /var/azuracast/storage/uploads \
  /var/azuracast/storage/shoutcast2 \
  /var/azuracast/storage/stereo_tool \
  /var/azuracast/storage/geoip \
  /var/azuracast/storage/sftpgo \
  /var/azuracast/storage/acme
