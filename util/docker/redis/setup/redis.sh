#!/bin/bash
set -e
set -x

apt-get install -y --no-install-recommends redis-server

cp /bd_build/redis/redis/redis.conf /etc/redis/redis.conf
chown redis:redis /etc/redis/redis.conf
