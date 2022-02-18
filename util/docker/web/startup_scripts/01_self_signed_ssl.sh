#!/bin/bash

if [ -f /etc/nginx/certs/default.crt ]; then
    rm -rf /etc/nginx/certs/default.key || true
    rm -rf /etc/nginx/certs/default.crt || true
fi

# Generate a self-signed certificate if one doesn't exist in the certs path.
if [ ! -f /etc/nginx/certs/default.crt ]; then
    echo "Generating self-signed certificate..."

    openssl req -new -nodes -x509 -subj "/C=US/ST=Texas/L=Austin/O=IT/CN=localhost" \
        -days 365 -extensions v3_ca \
        -keyout /etc/nginx/certs/default.key \
        -out /etc/nginx/certs/default.crt
fi
