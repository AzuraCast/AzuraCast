#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

$minimal_apt_get_install nginx nginx-common nginx-extras openssl

# Install nginx and configuration
cp /bd_build/nginx/nginx.conf /etc/nginx/nginx.conf
cp /bd_build/nginx/azuracast.conf /etc/nginx/conf.d/azuracast.conf

mkdir -p /etc/nginx/azuracast.conf.d/

# Create nginx temp dirs
mkdir -p /tmp/azuracast_nginx_client /tmp/azuracast_fastcgi_temp
touch /tmp/azuracast_nginx_client/.tmpreaper
touch /tmp/azuracast_fastcgi_temp/.tmpreaper
chmod -R 777 /tmp/azuracast_*
