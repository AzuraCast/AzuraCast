#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

cd /tmp

wget --quiet -O dbip-city-lite.mmdb.gz https://download.db-ip.com/free/dbip-city-lite-2020-01.mmdb.gz
gunzip dbip-city-lite.mmdb.gz

mv dbip-city-lite.mmdb /var/azuracast/dbip/

chown -R azuracast:azuracast /var/azuracast/dbip