#!/bin/bash
set -e
set -x

add-apt-repository -y ppa:chris-lea/redis-server
apt-get update

apt-get install -y --no-install-recommends redis-server

cp /bd_build/redis/redis/redis.conf /etc/redis/redis.conf
chown redis:redis /etc/redis/redis.conf
