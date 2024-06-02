#!/bin/bash
set -e
set -x

curl -fsSL https://packages.redis.io/gpg | sudo gpg --dearmor -o /usr/share/keyrings/redis-archive-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/redis-archive-keyring.gpg] https://packages.redis.io/deb $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/redis.list

apt-get update
apt-get install -y --no-install-recommends redis-server=6:7.2.4-1rl1~bookworm1 redis-tools=6:7.2.4-1rl1~bookworm1

cp /bd_build/redis/redis/redis.conf /etc/redis/redis.conf
chown redis:redis /etc/redis/redis.conf

mkdir -p /run/redis
chown redis:redis /run/redis
