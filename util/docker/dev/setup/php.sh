#!/bin/bash
set -e
set -x

source /etc/php/.version

# Install dev PHP stuff
apt-get install -y --no-install-recommends php${PHP_VERSION}-fpm php${PHP_VERSION}-xdebug

mkdir -p /run/php
touch /run/php/php${PHP_VERSION}-fpm.pid

cp /bd_build/dev/php/www.conf /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf

# Install PHP SPX profiler
apt-get install -y --no-install-recommends php${PHP_VERSION}-dev zlib1g-dev build-essential

mkdir -p /bd_build/web/php-spx
cd /bd_build/web/php-spx

git clone https://github.com/NoiseByNorthwest/php-spx.git .
phpize
./configure
make
sudo make install

apt-get remove --purge -y php${PHP_VERSION}-dev zlib1g-dev build-essential

echo "extension=spx.so" > /etc/php/${PHP_VERSION}/mods-available/30-spx.ini
ln -s /etc/php/${PHP_VERSION}/mods-available/30-spx.ini /etc/php/${PHP_VERSION}/cli/conf.d/30-spx.ini
ln -s /etc/php/${PHP_VERSION}/mods-available/30-spx.ini /etc/php/${PHP_VERSION}/fpm/conf.d/30-spx.ini
