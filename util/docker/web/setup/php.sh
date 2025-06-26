#!/bin/bash
set -e
set -x

install-php-extensions @composer \
  gd curl xml zip \
  gmp pdo_mysql mbstring intl \
  redis maxminddb uuid \
  ffi sockets

rm -rf /usr/local/etc/php-fpm.d/*

cp /bd_build/web/php/php.ini.tmpl /usr/local/etc/php/php.ini.tmpl
cp /bd_build/web/php/www.conf.tmpl /usr/local/etc/php-fpm.conf.tmpl

# Enable FFI (for StereoTool inspection)
echo 'ffi.enable="true"' >> /usr/local/etc/php/conf.d/ffi.ini
