#!/usr/bin/env bash

# Add cron job
echo "Installing cron job..."
crontab -u azuracast $www_base/util/vagrant_cron

service cron start