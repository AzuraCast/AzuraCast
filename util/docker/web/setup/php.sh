#!/bin/bash
set -e
set -x

PHP_VERSION=8.2

# PPA set up in 00_packages.sh

apt-get install -y --no-install-recommends php${PHP_VERSION}-fpm php${PHP_VERSION}-cli php${PHP_VERSION}-gd \
  php${PHP_VERSION}-curl php${PHP_VERSION}-xml php${PHP_VERSION}-zip php${PHP_VERSION}-bcmath \
  php${PHP_VERSION}-gmp php${PHP_VERSION}-mysqlnd php${PHP_VERSION}-mbstring php${PHP_VERSION}-intl \
  php${PHP_VERSION}-redis php${PHP_VERSION}-maxminddb php${PHP_VERSION}-xdebug

# Copy PHP configuration
echo "PHP_VERSION=$PHP_VERSION" >> /etc/php/.version

mkdir -p /run/php
touch /run/php/php${PHP_VERSION}-fpm.pid

cp /bd_build/web/php/php.ini.tmpl /etc/php/${PHP_VERSION}/fpm/05-azuracast.ini.tmpl
cp /bd_build/web/php/www.conf.tmpl /etc/php/${PHP_VERSION}/fpm/www.conf.tmpl

# Enable FFI (for StereoTool inspection)
echo 'ffi.enable="true"' >> /etc/php/${PHP_VERSION}/mods-available/ffi.ini

# Install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
