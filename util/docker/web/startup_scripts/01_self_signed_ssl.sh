#!/bin/bash

mkdir -p /var/azuracast/acme/challenges || true

if [ -f /var/azuracast/acme/default.crt ]; then
    rm -rf /var/azuracast/acme/default.key || true
    rm -rf /var/azuracast/acme/default.crt || true
fi

# Generate a self-signed certificate if one doesn't exist in the certs path.
if [ ! -f /var/azuracast/acme/default.crt ]; then
    echo "Generating self-signed certificate..."

    openssl req -new -nodes -x509 -subj "/C=US/ST=Texas/L=Austin/O=IT/CN=localhost" \
        -days 365 -extensions v3_ca \
        -keyout /var/azuracast/acme/default.key \
        -out /var/azuracast/acme/default.crt
fi

if [ ! -f /var/azuracast/acme/ssl.crt ]; then
    ln -s /var/azuracast/acme/default.key /var/azuracast/acme/ssl.key
    ln -s /var/azuracast/acme/default.crt /var/azuracast/acme/ssl.crt
fi

chown -R azuracast:azuracast /var/azuracast/acme || true
chmod -R u=rwX,go=rX /var/azuracast/acme || true
