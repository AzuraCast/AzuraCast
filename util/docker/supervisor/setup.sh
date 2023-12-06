#!/bin/bash
set -e
set -x

export DEBIAN_FRONTEND=noninteractive

apt-get update

# Install common scripts
# cp -rT /bd_build/supervisor/scripts/ /usr/local/bin

# cp -rT /bd_build/supervisor/startup_scripts/. /etc/my_init.d/

# cp -rT /bd_build/supervisor/service.minimal/. /etc/supervisor/minimal.conf.d/

# cp -rT /bd_build/supervisor/service.full/. /etc/supervisor/full.conf.d/

# Run service setup for all setup scripts
for f in /bd_build/supervisor/setup/*.sh; do
  bash "$f" -H 
done
