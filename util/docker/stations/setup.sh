#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

apt-get update

# Install common scripts
# cp -rT /bd_build/stations/scripts/ /usr/local/bin
# chmod -R a+x /usr/local/bin

# cp -rT /bd_build/stations/startup_scripts/. /etc/my_init.d/
# chmod -R +x /etc/my_init.d

cp -rT /bd_build/stations/runit/. /etc/service/
chmod -R +x /etc/service

# Run service setup for all setup scripts
for f in /bd_build/stations/setup/*.sh; do
  bash "$f" -H 
done
