#!/bin/bash
set -e
source /bd_build_final/buildconfig
set -x

# Install common scripts
cp -rT /bd_build_final/scripts/ /usr/local/bin
chmod -R a+x /usr/local/bin

# Install runit scripts
cp -rT /bd_build_final/startup_scripts/. /etc/my_init.d/
cp -rT /bd_build_final/service/. /etc/service/
cp -rT /bd_build_final/service_standalone/. /etc/service_standalone/

chmod -R +x /etc/service
chmod -R +x /etc/service_standalone
chmod -R +x /etc/my_init.d

# Run service setup for all setup scripts
for f in /bd_build_final/setup/*.sh; do
  bash "$f" -H
done
