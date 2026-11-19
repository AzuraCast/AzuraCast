#!/bin/bash
set -e
set -x

# Configure and install official ngixn from repository
curl -fsSL https://nginx.org/keys/nginx_signing.key | gpg --dearmor -o /usr/share/keyrings/nginx-archive-keyring.gpg

echo "deb [signed-by=/usr/share/keyrings/nginx-archive-keyring.gpg] \
https://nginx.org/packages/mainline/debian `lsb_release -cs` nginx" \
  | tee /etc/apt/sources.list.d/nginx.list

echo -e "Package: *\nPin: origin nginx.org\nPin: release o=nginx\nPin-Priority: 900\n" \
  | tee /etc/apt/preferences.d/99nginx

apt update
apt install -y --no-install-recommends nginx

# Install nginx and configuration
cp /bd_build/web/nginx/proxy_params.conf /etc/nginx/proxy_params
cp /bd_build/web/nginx/nginx.conf.tmpl /etc/nginx/nginx.conf.tmpl
cp /bd_build/web/nginx/azuracast.conf.tmpl /etc/nginx/azuracast.conf.tmpl

# Change M3U8 MIME type to "application/x-mpegurl" for broader compatibility.
sed -i 's#application/vnd.apple.mpegurl#application/x-mpegurl#' /etc/nginx/mime.types

mkdir -p /etc/nginx/modules-enabled/ \
  /etc/nginx/sites-available/ \
  /etc/nginx/sites-enabled/ \
  /etc/nginx/azuracast.conf.d/

rm -rf /etc/nginx/conf.d/default.conf

# Create nginx temp dirs
mkdir -p /tmp/nginx_client \
  /tmp/nginx_fastcgi \
  /tmp/nginx_cache

touch /tmp/nginx_client/.tmpreaper
touch /tmp/nginx_fastcgi/.tmpreaper
touch /tmp/nginx_cache/.tmpreaper

chmod -R 777 /tmp/nginx_*

# Fetch nginx bot blocker install script
wget https://raw.githubusercontent.com/mitchellkrogza/nginx-ultimate-bad-bot-blocker/master/install-ngxblocker -O /usr/local/sbin/install-ngxblocker
chmod +x /usr/local/sbin/install-ngxblocker
