#!/bin/bash
set -e
set -x

# Install dev PHP stuff
install-php-extensions xdebug spx

rm -rf /usr/local/etc/php-fpm.d/*
