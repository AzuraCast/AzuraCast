#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

add-apt-repository -y ppa:chris-lea/redis-server
apt-get update

$minimal_apt_get_install redis-server

cp /bd_build/redis/redis/redis.conf /etc/redis/redis.conf
chown redis:redis /etc/redis/redis.conf
