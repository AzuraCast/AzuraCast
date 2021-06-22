#!/bin/bash
set -e
source /bd_build_base/buildconfig
set -x

# Install scripts commonly used during setup.
$minimal_apt_get_install runit curl wget tar zip unzip git rsync tzdata gpg-agent openssh-client

# Run service setup for all setup scripts
for f in /bd_build_base/setup/*.sh; do
  bash "$f" -H
done
