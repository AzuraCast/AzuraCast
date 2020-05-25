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

# SSL self-signed cert generation
openssl req -new -nodes -x509 -subj "/C=US/ST=Texas/L=Austin/O=IT/CN=localhost" \
    -days 365 -extensions v3_ca \
    -keyout /etc/nginx/selfsigned.key \
    -out /etc/nginx/selfsigned.crt

# Generate the dhparam.pem file (takes a long time)
openssl dhparam -dsaparam -out /etc/nginx/dhparam.pem 4096
