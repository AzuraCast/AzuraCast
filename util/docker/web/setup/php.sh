#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

add-apt-repository -y ppa:ondrej/php
apt-get update

$minimal_apt_get_install php8.0-fpm php8.0-cli php8.0-gd \
    php8.0-curl php8.0-xml php8.0-zip php8.0-bcmath php8.0-gmp \
    php8.0-mysqlnd php8.0-mbstring php8.0-intl php8.0-redis \
    mariadb-client

# Copy PHP configuration
mkdir -p /run/php
touch /run/php/php8.0-fpm.pid

cp /bd_build/php/php.ini.tmpl /etc/php/8.0/fpm/05-azuracast.ini.tmpl
cp /bd_build/php/www.conf.tmpl /etc/php/8.0/fpm/www.conf.tmpl

# Install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
