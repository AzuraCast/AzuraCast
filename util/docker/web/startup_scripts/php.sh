#!/bin/bash

# Copy the php.ini template to its destination.
dockerize -template "/etc/php/7.4/fpm/05-azuracast.ini.tmpl:/etc/php/7.4/fpm/conf.d/05-azuracast.ini" -template "/etc/php/7.4/fpm/www.conf.tmpl:/etc/php/7.4/fpm/pool.d/www.conf" cp /etc/php/7.4/fpm/conf.d/05-azuracast.ini /etc/php/7.4/cli/conf.d/05-azuracast.ini