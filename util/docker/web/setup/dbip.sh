#!/bin/bash
set -e
set -x

cd /tmp

export TZ="America/Chicago"

YEAR=$(date +'%Y')
MONTH=$(date +'%m')

export TZ="UTC"

wget --quiet -O dbip-city-lite.mmdb.gz "https://download.db-ip.com/free/dbip-city-lite-${YEAR}-${MONTH}.mmdb.gz"
gunzip dbip-city-lite.mmdb.gz

mv dbip-city-lite.mmdb /var/azuracast/dbip/

chown -R azuracast:azuracast /var/azuracast/dbip
