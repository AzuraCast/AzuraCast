#!/bin/bash
set -e
set -x

apt-get install -y --no-install-recommends valkey-server valkey-tools

cp /bd_build/redis/redis/redis.conf /etc/valkey/valkey.conf
chown valkey:valkey /etc/valkey/valkey.conf

mkdir -p /run/redis
chown valkey:valkey /run/redis
