#!/bin/bash
set -e
set -x

apt-get install -y --no-install-recommends valkey-server/bookworm-backports valkey-tools/bookworm-backports

cp /bd_build/redis/redis/redis.conf /etc/valkey/valkey.conf
chown valkey:valkey /etc/valkey/valkey.conf

mkdir -p /run/redis
chown valkey:valkey /run/redis
