#!/bin/bash
set -e
set -x

apt-get install -y --no-install-recommends redict-server/bookworm-backports redict-tools/bookworm-backports

cp /bd_build/redis/redis/redis.conf /etc/redict/redict.conf
chown redict:redict /etc/redict/redict.conf

mkdir -p /run/redis
chown redict:redict /run/redis
