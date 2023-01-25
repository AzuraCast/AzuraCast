#!/bin/bash
set -e
set -x

mkdir -p /var/azuracast/meilisearch/persist

cp /bd_build/web/meilisearch/config.toml /var/azuracast/meilisearch/config.toml
