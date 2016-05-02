#!/usr/bin/env bash

export util_base="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
export app_base = "$(dirname "$util_base")"
export www_base = $app_base
export tmp_base= $app_base/tmp

#
# Vagrant-specific Deploy Commands
#

export DEBIAN_FRONTEND=noninteractive

# Add Vagrant user to the sudoers group
echo 'vagrant ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers

# Set up swap partition
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo "/swapfile   none    swap    sw    0   0" >> /etc/fstab

# Set up server
apt-get update

apt-get -q -y install software-properties-common python-software-properties
apt-get -q -y install vim git curl nginx

# Trigger mlocate reindex.
updatedb

# Update Vagrant account permissions.
usermod -G vagrant www-data
usermod -G vagrant nobody
usermod -G www-data vagrant

#
# Run Common Installers
#

cd $util_base;
chmod a+x ./install_app.sh
chmod a+x ./install_radio.sh

./install_app.sh
./install_radio.sh

#
# Post-installer messages
#

echo "One-time setup complete!"
echo "Complete remaining setup steps at http://localhost:8080"