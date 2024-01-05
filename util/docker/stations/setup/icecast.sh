#!/bin/bash
set -e
set -x

# Icecast is built and imported in its own Docker container.

apt-get install -q -y --no-install-recommends libxml2 openssl
