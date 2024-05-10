#!/bin/bash

dockerize -template "/usr/local/etc/php/php.ini.tmpl:/usr/local/etc/php/php.ini" \
  -template "/usr/local/etc/php-fpm.conf.tmpl:/usr/local/etc/php-fpm.conf"
