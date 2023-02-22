#!/bin/bash

bool() {
  case "$1" in
  Y* | y* | true | TRUE | 1) return 0 ;;
  esac
  return 1
}

ENABLE_MEILISEARCH=${ENABLE_MEILISEARCH:-true}

if ! bool "$ENABLE_MEILISEARCH"; then
    echo "Meilisearch is disabled..."
    rm -rf /etc/supervisor/full.conf.d/meilisearch.conf
fi
