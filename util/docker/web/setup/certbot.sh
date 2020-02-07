#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

add-apt-repository -y ppa:certbot/certbot

apt-get update

$minimal_apt_get_install certbot openssl

mkdir -p /var/www/letsencrypt /var/lib/letsencrypt /etc/letsencrypt/renewal-hooks/deploy /var/log/letsencrypt
chown -R azuracast:azuracast /var/www/letsencrypt /var/lib/letsencrypt /etc/letsencrypt /var/log/letsencrypt

# SSL self-signed cert generation
openssl req -new -nodes -x509 -subj "/C=US/ST=Texas/L=Austin/O=IT/CN=localhost" \
    -days 365 -extensions v3_ca \
    -keyout /etc/letsencrypt/selfsigned.key \
	-out /etc/letsencrypt/selfsigned.crt

ln -s /etc/letsencrypt/selfsigned.key /etc/letsencrypt/ssl.key
ln -s /etc/letsencrypt/selfsigned.crt /etc/letsencrypt/ssl.crt

# Add nginx restart hook.
cp /bd_build/letsencrypt/01-reload-nginx.sh /etc/letsencrypt/renewal-hooks/deploy/01-reload-nginx.sh
chmod 755 /etc/letsencrypt/renewal-hooks/deploy/*