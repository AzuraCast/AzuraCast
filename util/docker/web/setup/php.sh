#!/bin/bash
set -e
set -x

# Install PHP extensions manually for Railway compatibility
apt-get update
apt-get install -y --no-install-recommends \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    libgmp-dev \
    libonig-dev \
    libmaxminddb-dev \
    libuuid1 \
    libffi-dev

# Configure and install PHP extensions
docker-php-ext-configure gd --with-freetype --with-jpeg
docker-php-ext-install -j$(nproc) \
    gd \
    curl \
    xml \
    zip \
    gmp \
    pdo_mysql \
    mbstring \
    intl \
    ffi \
    sockets

# Install PECL extensions
pecl install redis maxminddb uuid
docker-php-ext-enable redis maxminddb uuid

# Install Composer manually
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

rm -rf /usr/local/etc/php-fpm.d/*

cp /bd_build/web/php/php.ini.tmpl /usr/local/etc/php/php.ini.tmpl
cp /bd_build/web/php/www.conf.tmpl /usr/local/etc/php-fpm.conf.tmpl

# Enable FFI (for StereoTool inspection)
echo 'ffi.enable="true"' >> /usr/local/etc/php/conf.d/ffi.ini