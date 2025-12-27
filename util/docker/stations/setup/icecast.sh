#!/bin/bash
set -e
set -x

# Icecast is built and imported in its own Docker container.

apt-get install -q -y --no-install-recommends librhash1 libxml2 libxslt1.1 openssl
