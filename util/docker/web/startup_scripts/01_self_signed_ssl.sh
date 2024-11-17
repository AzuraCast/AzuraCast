#!/bin/bash

if [ -z "$ACME_DIR" ]; then
  if [ -d "/var/azuracast/acme" ]; then
    export ACME_DIR="/var/azuracast/acme"
  else
    export ACME_DIR="/var/azuracast/storage/acme"
  fi
fi

mkdir -p "$ACME_DIR/challenges" || true

# If the final cert path is a symlink (working or otherwise), remove it to re-establish it in the next step.
if [ ! -e "$ACME_DIR/ssl.crt" ] || [ -L "$ACME_DIR/ssl.crt" ]; then
  rm -rf "$ACME_DIR/ssl.key" || true
  rm -rf "$ACME_DIR/ssl.crt" || true
fi

if [ ! -f "$ACME_DIR/ssl.crt" ]; then
    if [ -f "$ACME_DIR/custom.crt" ]; then
        ln -s "$ACME_DIR/custom.key" "$ACME_DIR/ssl.key"
        ln -s "$ACME_DIR/custom.crt" "$ACME_DIR/ssl.crt"
    elif [ -f "$ACME_DIR/acme.crt" ]; then
        ln -s "$ACME_DIR/acme.key" "$ACME_DIR/ssl.key"
        ln -s "$ACME_DIR/acme.crt" "$ACME_DIR/ssl.crt"
    else
        # Always generate a new self-signed cert if it's in use.
        rm -rf "$ACME_DIR/default.key" || true
        rm -rf "$ACME_DIR/default.crt" || true

        if [ ! -f "$ACME_DIR/default.crt" ]; then
            echo "Generating self-signed certificate..."

            openssl req -new -nodes -x509 -subj "/C=US/ST=Texas/L=Austin/O=IT/CN=localhost" \
                -days 365 -extensions v3_ca \
                -keyout "$ACME_DIR/default.key" \
                -out "$ACME_DIR/default.crt"
        fi

        ln -s "$ACME_DIR/default.key" "$ACME_DIR/ssl.key"
        ln -s "$ACME_DIR/default.crt" "$ACME_DIR/ssl.crt"
    fi
fi

chown -R azuracast:azuracast "$ACME_DIR" || true
chmod -R u=rwX,go=rX "$ACME_DIR" || true
