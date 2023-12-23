#!/bin/bash

source /etc/php/.version

dockerize -template "/etc/php/${PHP_VERSION}/cli/05-azuracast.ini.tmpl:/etc/php/${PHP_VERSION}/cli/conf.d/05-azuracast.ini"
