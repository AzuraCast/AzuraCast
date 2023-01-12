#!/bin/bash
set -e
set -x

# Package installation handled in 00_packages.sh

# Install nginx and configuration
cp /bd_build/web/nginx/proxy_params.conf /etc/nginx/proxy_params
cp /bd_build/web/nginx/nginx.conf.tmpl /etc/nginx/nginx.conf.tmpl
cp /bd_build/web/nginx/azuracast.conf.tmpl /etc/nginx/azuracast.conf.tmpl

mkdir -p /etc/nginx/azuracast.conf.d/

# Create nginx temp dirs
mkdir -p /tmp/app_nginx_client /tmp/app_fastcgi_temp
touch /tmp/app_nginx_client/.tmpreaper
touch /tmp/app_fastcgi_temp/.tmpreaper
chmod -R 777 /tmp/app_*
