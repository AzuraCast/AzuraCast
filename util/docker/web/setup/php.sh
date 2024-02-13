#!/bin/bash
set -e
set -x

install-php-extensions @composer \
  gd curl xml zip \
  gmp pdo_mysql mbstring intl \
  redis maxminddb \
  ffi sockets

cp /bd_build/web/php/php.ini.tmpl /usr/local/etc/php/php.ini.tmpl

# Enable FFI (for StereoTool inspection)
echo 'ffi.enable="true"' >> /usr/local/etc/php/conf.d/ffi.ini
