#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

add-apt-repository -y ppa:ondrej/php
apt-get update

$minimal_apt_get_install php7.4-fpm php7.4-cli php7.4-gd \
    php7.4-curl php7.4-xml php7.4-zip php7.4-bcmath php7.4-gmp \
    php7.4-mysqlnd php7.4-mbstring php7.4-intl php7.4-redis \
    php7.4-maxminddb php7.4-xdebug \
    mariadb-client

# Copy PHP configuration
mkdir -p /run/php
touch /run/php/php7.4-fpm.pid

cp /bd_build/php/php.ini.tmpl /etc/php/7.4/fpm/05-azuracast.ini.tmpl
cp /bd_build/php/www.conf.tmpl /etc/php/7.4/fpm/www.conf.tmpl

# Install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# Install PHP SPX profiler
$minimal_apt_get_install php7.4-dev zlib1g-dev build-essential

cd /bd_build
git clone https://github.com/NoiseByNorthwest/php-spx.git
cd php-spx
phpize
./configure
make
sudo make install

apt-get remove --purge -y php7.4-dev zlib1g-dev build-essential

echo "extension=spx.so" > /etc/php/7.4/cli/conf.d/30-spx.ini
echo "extension=spx.so" > /etc/php/7.4/fpm/conf.d/30-spx.ini
