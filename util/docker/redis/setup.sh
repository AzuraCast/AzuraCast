#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

apt-get update

# Install common scripts
# cp -rT /bd_build/redis/scripts/ /usr/local/bin
# chmod -R a+x /usr/local/bin

# cp -rT /bd_build/redis/startup_scripts/. /etc/my_init.d/
# chmod -R +x /etc/my_init.d

cp -rT /bd_build/redis/runit/. /etc/service/
chmod -R +x /etc/service

# Run service setup for all setup scripts
for f in /bd_build/redis/setup/*.sh; do
  bash "$f" -H 
done
