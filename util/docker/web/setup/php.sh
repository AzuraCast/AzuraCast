#!/bin/bash
set -e
set -x

PHP_VERSION=8.1

add-apt-repository -y ppa:ondrej/php
apt-get update

apt-get install -y --no-install-recommends php${PHP_VERSION}-fpm php${PHP_VERSION}-cli php${PHP_VERSION}-gd \
  php${PHP_VERSION}-curl php${PHP_VERSION}-xml php${PHP_VERSION}-zip php${PHP_VERSION}-bcmath \
  php${PHP_VERSION}-gmp php${PHP_VERSION}-mysqlnd php${PHP_VERSION}-mbstring php${PHP_VERSION}-intl \
  php${PHP_VERSION}-redis php${PHP_VERSION}-maxminddb php${PHP_VERSION}-xdebug \
  mariadb-client

# Copy PHP configuration
echo "PHP_VERSION=$PHP_VERSION" >> /etc/php/.version

mkdir -p /run/php
touch /run/php/php${PHP_VERSION}-fpm.pid

cp /bd_build/web/php/php.ini.tmpl /etc/php/${PHP_VERSION}/fpm/05-azuracast.ini.tmpl
cp /bd_build/web/php/www.conf.tmpl /etc/php/${PHP_VERSION}/fpm/www.conf.tmpl

# Install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# Download PHP-FPM healthcheck script
apt-get install -y --no-install-recommends libfcgi-bin

wget -O /usr/local/bin/php-fpm-healthcheck \
  https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
  && chmod +x /usr/local/bin/php-fpm-healthcheck

# Install PHP SPX profiler
apt-get install -y --no-install-recommends php${PHP_VERSION}-dev zlib1g-dev build-essential

cd /bd_build
git clone https://github.com/NoiseByNorthwest/php-spx.git
cd php-spx
phpize
./configure
make
sudo make install

apt-get remove --purge -y php${PHP_VERSION}-dev zlib1g-dev build-essential

echo "extension=spx.so" > /etc/php/${PHP_VERSION}/mods-available/30-spx.ini
ln -s /etc/php/${PHP_VERSION}/mods-available/30-spx.ini /etc/php/${PHP_VERSION}/cli/conf.d/30-spx.ini
ln -s /etc/php/${PHP_VERSION}/mods-available/30-spx.ini /etc/php/${PHP_VERSION}/fpm/conf.d/30-spx.ini
