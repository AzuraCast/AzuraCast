#!/bin/bash
set -e
set -x

mkdir -p /etc/cron.d/
cp -r /bd_build/web/cron/. /etc/cron.d/
