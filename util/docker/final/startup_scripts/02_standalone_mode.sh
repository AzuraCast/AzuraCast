#!/bin/bash

bool() {
    case "$1" in
    Y* | y* | true | TRUE | 1) return 0 ;;
    esac
    return 1
}

STANDALONE_MODE=${AZURACAST_DOCKER_STANDALONE_MODE:-0}

if bool "$STANDALONE_MODE"; then
  echo "Running in standalone mode; enabling optional services..."

  cp /etc/service_standalone/. /etc/service/
else
  echo "Not running in standalone mode; skipping optional services."
fi
