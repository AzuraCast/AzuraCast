#!/bin/bash

# Copy the php.ini template to its destination.
source /etc/php/.version

dockerize -template "/etc/php/${PHP_VERSION}/fpm/05-azuracast.ini.tmpl:/etc/php/${PHP_VERSION}/fpm/conf.d/05-azuracast.ini" \
  -template "/etc/php/${PHP_VERSION}/fpm/www.conf.tmpl:/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf" \
  cp /etc/php/${PHP_VERSION}/fpm/conf.d/05-azuracast.ini /etc/php/${PHP_VERSION}/cli/conf.d/05-azuracast.ini
