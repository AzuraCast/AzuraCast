#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

# wget -qO- https://repos.influxdata.com/influxdb.key | apt-key add -
# echo "deb https://repos.influxdata.com/ubuntu bionic stable" | tee /etc/apt/sources.list.d/influxdb.list

apt-get update

$minimal_apt_get_install influxdb