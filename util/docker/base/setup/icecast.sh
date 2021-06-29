#!/bin/bash
set -e
source /bd_build_base/buildconfig
set -x

# Only install Icecast deps (Icecast is handled by another container).
$minimal_apt_get_install libxml2 libxslt1-dev libvorbis-dev openssl

mkdir -p /etc/nginx
chown azuracast:azuracast /etc/nginx

openssl req -new -nodes -x509 -subj "/C=US/ST=Texas/L=Austin/O=IT/CN=localhost" \
  -days 365 -extensions v3_ca \
  -keyout /etc/nginx/ssl.key \
  -out /etc/nginx/ssl.crt
