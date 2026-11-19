#!/bin/bash

bool() {
  case "$1" in
  Y* | y* | true | TRUE | 1) return 0 ;;
  esac
  return 1
}

# Determine the current uploads dir for the installation.
if [ -z "$UPLOADS_DIR" ]; then
  if [ -d "/var/azuracast/uploads" ]; then
    export UPLOADS_DIR="/var/azuracast/uploads"
  else
    export UPLOADS_DIR="/var/azuracast/storage/uploads"
  fi
fi

if [ -z "$ACME_DIR" ]; then
  if [ -d "/var/azuracast/acme" ]; then
    export ACME_DIR="/var/azuracast/acme"
  else
    export ACME_DIR="/var/azuracast/storage/acme"
  fi
fi

# Copy the nginx template to its destination.
dockerize -template "/etc/nginx/nginx.conf.tmpl:/etc/nginx/nginx.conf" \
    -template "/etc/nginx/azuracast.conf.tmpl:/etc/nginx/sites-available/default.vhost"

ln -s /etc/nginx/sites-available/default.vhost /etc/nginx/sites-enabled/default.vhost

# Install the nginx blocker if environment variables are set.
NGINX_BLOCK_BOTS=${NGINX_BLOCK_BOTS:-false}

if bool "$NGINX_BLOCK_BOTS"; then
  echo "Installing Nginx bot blocker..."

  install-ngxblocker -x -q

  chmod +x /usr/local/sbin/setup-ngxblocker
  chmod +x /usr/local/sbin/update-ngxblocker

  setup-ngxblocker -x -l "*"
fi
