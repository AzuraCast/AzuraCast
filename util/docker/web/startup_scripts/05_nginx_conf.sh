#!/bin/bash

# Determine the current uploads dir for the installation.
if [ -z "$UPLOADS_DIR" ]; then
  if [ -d "/var/azuracast/uploads" ]; then
    export UPLOADS_DIR="/var/azuracast/uploads"
  else
    export UPLOADS_DIR="/var/azuracast/storage/uploads"
  fi
fi

# Copy the nginx template to its destination.
dockerize -template "/etc/nginx/nginx.conf.tmpl:/etc/nginx/nginx.conf" \
    -template "/etc/nginx/azuracast.conf.tmpl:/etc/nginx/sites-available/default"
