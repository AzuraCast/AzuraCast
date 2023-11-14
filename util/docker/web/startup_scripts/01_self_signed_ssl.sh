#!/bin/bash

if [ -z "$ACME_DIR" ]; then
  if [ -d "/var/azuracast/acme" ]; then
    export ACME_DIR="/var/azuracast/acme"
  else
    export ACME_DIR="/var/azuracast/storage/acme"
  fi
fi

mkdir -p "$ACME_DIR/challenges" || true

if [ -f "$ACME_DIR/default.crt" ]; then
    rm -rf "$ACME_DIR/default.key" || true
    rm -rf "$ACME_DIR/default.crt" || true
fi

# Generate a self-signed certificate if one doesn't exist in the certs path.
if [ ! -f "$ACME_DIR/default.crt" ]; then
    echo "Generating self-signed certificate..."

    openssl req -new -nodes -x509 -subj "/C=US/ST=Texas/L=Austin/O=IT/CN=localhost" \
        -days 365 -extensions v3_ca \
        -keyout "$ACME_DIR/default.key" \
        -out "$ACME_DIR/default.crt"
fi

if [ ! -f "$ACME_DIR/ssl.crt" ]; then
    ln -s "$ACME_DIR/default.key" "$ACME_DIR/ssl.key"
    ln -s "$ACME_DIR/default.crt" "$ACME_DIR/ssl.crt"
fi

chown -R azuracast:azuracast "$ACME_DIR" || true
chmod -R u=rwX,go=rX "$ACME_DIR" || true
