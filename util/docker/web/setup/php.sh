#!/bin/bash
set -e
set -x

PHP_VERSION=8.2

curl -S "https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c" \
  | sudo gpg --batch --yes --dearmor --output "/etc/apt/keyrings/php.gpg"

echo "deb [signed-by=/etc/apt/keyrings/php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" >> /etc/apt/sources.list.d/php.list
echo "deb-src [signed-by=/etc/apt/keyrings/php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" >> /etc/apt/sources.list.d/php.list

apt-get update

apt-get install -y --no-install-recommends php${PHP_VERSION}-cli php${PHP_VERSION}-gd \
  php${PHP_VERSION}-curl php${PHP_VERSION}-xml php${PHP_VERSION}-zip \
  php${PHP_VERSION}-gmp php${PHP_VERSION}-mysqlnd php${PHP_VERSION}-mbstring php${PHP_VERSION}-intl \
  php${PHP_VERSION}-redis php${PHP_VERSION}-maxminddb

# Copy PHP configuration
echo "PHP_VERSION=$PHP_VERSION" >> /etc/php/.version

cp /bd_build/web/php/php.ini.tmpl /etc/php/${PHP_VERSION}/05-azuracast.ini.tmpl

# Enable FFI (for StereoTool inspection)
echo 'ffi.enable="true"' >> /etc/php/${PHP_VERSION}/mods-available/ffi.ini

# Install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
