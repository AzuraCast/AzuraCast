#!/bin/bash

export DEBIAN_FRONTEND=noninteractive

INSTALL_PACKAGES_ON_STARTUP=${INSTALL_PACKAGES_ON_STARTUP:-""}

if [ ! -z "$INSTALL_PACKAGES_ON_STARTUP" ]; then
  echo "Installing extra packages..."

  apt-get update
  apt-get install -y --no-install-recommends $INSTALL_PACKAGES_ON_STARTUP
  apt-get clean
  rm -rf /var/lib/apt/lists/*
  rm -rf /tmp/tmp*
fi
