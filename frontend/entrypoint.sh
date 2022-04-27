#!/usr/bin/env bash

fixuid

cd /data/frontend
npm ci

exec "$@"
