#!/bin/bash
set -e
set -x

## Prevent initramfs updates from trying to run grub and lilo.
## https://journal.paul.querna.org/articles/2013/10/15/docker-ubuntu-on-rackspace/
## http://bugs.debian.org/cgi-bin/bugreport.cgi?bug=594189
export INITRD=no

export DEBIAN_FRONTEND=noninteractive

## Enable Ubuntu Universe, Multiverse, and deb-src for main.
sed -i 's/^#\s*\(deb.*main restricted\)$/\1/g' /etc/apt/sources.list
sed -i 's/^#\s*\(deb.*universe\)$/\1/g' /etc/apt/sources.list
sed -i 's/^#\s*\(deb.*multiverse\)$/\1/g' /etc/apt/sources.list

# Pick specific Ubuntu mirror
# sed -i 's/archive.ubuntu.com/mirror.genesisadaptive.com/g' /etc/apt/sources.list
# sed -i 's/security.ubuntu.com/mirror.genesisadaptive.com/g' /etc/apt/sources.list

apt-get update

## Fix some issues with APT packages.
## See https://github.com/dotcloud/docker/issues/1024
dpkg-divert --local --rename --add /sbin/initctl
ln -sf /bin/true /sbin/initctl

# Add default timezone.
echo "UTC" > /etc/timezone

# Avoid ERROR: invoke-rc.d: policy-rc.d denied execution of start.
sed -i "s/^exit 101$/exit 0/" /usr/sbin/policy-rc.d 

## Replace the 'ischroot' tool to make it always return true.
## Prevent initscripts updates from breaking /dev/shm.
## https://journal.paul.querna.org/articles/2013/10/15/docker-ubuntu-on-rackspace/
## https://bugs.launchpad.net/launchpad/+bug/974584
dpkg-divert --local --rename --add /usr/bin/ischroot
ln -sf /bin/true /usr/bin/ischroot

# apt-utils fix for Ubuntu 16.04
apt-get install -y --no-install-recommends apt-utils

## Install HTTPS support for APT.
apt-get install -y --no-install-recommends apt-transport-https ca-certificates

## Upgrade all packages.
apt-get dist-upgrade -y --no-install-recommends -o Dpkg::Options::="--force-confold"

## Fix locale.
apt-get install -y --no-install-recommends language-pack-en

locale-gen en_US
update-locale LANG=en_US.UTF-8 LC_CTYPE=en_US.UTF-8

# Make init folders
mkdir -p /etc/my_init.d

# Install other common scripts.
apt-get install -y --no-install-recommends \
    tini gosu curl wget tar zip unzip xz-utils git rsync tzdata gnupg gpg-agent openssh-client

# Add scripts
cp -rT /bd_build/scripts/ /usr/local/bin
chmod -R a+x /usr/local/bin
