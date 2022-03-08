#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

apt-get update

# Install common scripts
# cp -rT /bd_build/redis/scripts/ /usr/local/bin

cp -rT /bd_build/redis/startup_scripts/. /etc/my_init.d/

cp -rT /bd_build/redis/service.minimal/. /etc/service.minimal/

# cp -rT /bd_build/redis/service.full/. /etc/service.full/

# Run service setup for all setup scripts
for f in /bd_build/redis/setup/*.sh; do
  bash "$f" -H 
done
