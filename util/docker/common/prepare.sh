#!/bin/bash
set -e
set -x

## Prevent initramfs updates from trying to run grub and lilo.
## https://journal.paul.querna.org/articles/2013/10/15/docker-ubuntu-on-rackspace/
## http://bugs.debian.org/cgi-bin/bugreport.cgi?bug=594189
export INITRD=no

export DEBIAN_FRONTEND=noninteractive

# Enable contrib and nonfree repos
sed -i 's/^Components: main$/& contrib non-free non-free-firmware/' /etc/apt/sources.list.d/debian.sources
# echo "deb http://deb.debian.org/debian bookworm-backports main" > /etc/apt/sources.list.d/backports.list

apt-get update

## Fix some issues with APT packages.
## See https://github.com/dotcloud/docker/issues/1024
dpkg-divert --local --rename --add /sbin/initctl
ln -sf /bin/true /sbin/initctl

# Add default timezone.
echo "UTC" > /etc/timezone

## Replace the 'ischroot' tool to make it always return true.
## Prevent initscripts updates from breaking /dev/shm.
## https://journal.paul.querna.org/articles/2013/10/15/docker-ubuntu-on-rackspace/
## https://bugs.launchpad.net/launchpad/+bug/974584
dpkg-divert --local --rename --add /usr/bin/ischroot
ln -sf /bin/true /usr/bin/ischroot

## Install HTTPS support for APT.
apt-get install -y --no-install-recommends apt-utils apt-transport-https ca-certificates

## Upgrade all packages.
apt-get dist-upgrade -y --no-install-recommends -o Dpkg::Options::="--force-confold"

## Fix locale.
apt-get install -y --no-install-recommends locales

echo "en_US.UTF-8 UTF-8" > /etc/locale.gen

locale-gen
dpkg-reconfigure locales

# Make init folders
mkdir -p /etc/my_init.d

# Install other common scripts.
apt-get install -y --no-install-recommends \
    lsb-release tini gosu curl wget tar zip unzip xz-utils git rsync tzdata gnupg gpg-agent openssh-client

# Add scripts
cp -rT /bd_build/scripts/ /usr/local/bin
chmod -R a+x /usr/local/bin
