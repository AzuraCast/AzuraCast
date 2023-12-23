#!/bin/bash
set -e
set -x

source /etc/php/.version

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
