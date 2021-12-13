#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

apt-get update

# Prevent systemd auto-startup
ln -s /dev/null /etc/systemd/system/beanstalkd.service

$minimal_apt_get_install beanstalkd
