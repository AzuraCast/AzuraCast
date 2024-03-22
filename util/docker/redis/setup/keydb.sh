#!/bin/bash
set -e
set -x

echo "deb https://download.keydb.dev/open-source-dist $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/keydb.list
wget -O /etc/apt/trusted.gpg.d/keydb.gpg https://download.keydb.dev/open-source-dist/keyring.gpg

apt-get update
apt-get install -y --no-install-recommends keydb-tools

mkdir -p /etc/redis
chown -R keydb:keydb /etc/redis
cp /bd_build/redis/redis/redis.conf /etc/redis/redis.conf

mkdir -p /run/redis
chown keydb:keydb /run/redis
