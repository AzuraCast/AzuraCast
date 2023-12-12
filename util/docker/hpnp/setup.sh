#!/bin/bash
set -e
set -x

export DEBIAN_FRONTEND=noninteractive

apt-get update

# Install common scripts
# cp -rT /bd_build/hpnp/scripts/ /usr/local/bin

# cp -rT /bd_build/hpnp/startup_scripts/. /etc/my_init.d/

# cp -rT /bd_build/hpnp/service.minimal/. /etc/supervisor/minimal.conf.d/

cp -rT /bd_build/hpnp/service.full/. /etc/supervisor/full.conf.d/

# Run service setup for all setup scripts
for f in /bd_build/hpnp/setup/*.sh; do
  bash "$f" -H
done
