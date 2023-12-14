#!/bin/bash
set -e
set -x

source /etc/php/.version

apt-get install -y --no-install-recommends php${PHP_VERSION}-xdebug
