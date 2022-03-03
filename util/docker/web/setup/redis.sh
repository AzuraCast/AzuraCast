#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

add-apt-repository -y ppa:chris-lea/redis-server
apt-get update

$minimal_apt_get_install redis-server
