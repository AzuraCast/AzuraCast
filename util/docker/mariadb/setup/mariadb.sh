#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

$minimal_apt_get_install tzdata

echo "1" >> /etc/container_environment/MARIADB_AUTO_UPGRADE

cp /bd_build/mariadb/mariadb/db.sql /docker-entrypoint-initdb.d/00-azuracast.sql
cp /bd_build/mariadb/mariadb/db.cnf.tmpl /etc/mysql/db.cnf.tmpl

mv /usr/local/bin/healthcheck.sh /usr/local/bin/db_healthcheck.sh
mv /usr/local/bin/docker-entrypoint.sh /usr/local/bin/db_entrypoint.sh
