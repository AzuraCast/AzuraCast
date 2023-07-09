#!/usr/bin/env bash

fixuid

cd /data/frontend
npm ci --include=dev

exec "$@"
