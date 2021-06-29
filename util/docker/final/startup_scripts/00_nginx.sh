#!/bin/bash

bool() {
  case "$1" in
  Y* | y* | true | TRUE | 1) return 0 ;;
  esac
  return 1
}

STANDALONE_MODE=${AZURACAST_DOCKER_STANDALONE_MODE:-0}

if bool "$STANDALONE_MODE"; then
  STANDALONE=1
else
  STANDALONE=0
fi

# Copy the nginx template to its destination.
STANDALONE=${STANDALONE} dockerize -template "/etc/nginx/azuracast.conf.tmpl:/etc/nginx/conf.d/azuracast.conf"
