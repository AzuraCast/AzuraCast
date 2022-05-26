#!/bin/bash

bool() {
  case "$1" in
  Y* | y* | true | TRUE | 1) return 0 ;;
  esac
  return 1
}

# If Redis is expressly disabled or the host is anything but localhost, disable Redis on this container.
ENABLE_REDIS=${ENABLE_REDIS:-true}

if [ "$REDIS_HOST" != "localhost" ] || ! bool "$ENABLE_REDIS"; then
    echo "Redis is disabled or host is not localhost; disabling Redis..."
    rm -rf /etc/supervisor/minimal.conf.d/redis.conf
fi
