#!/bin/bash
set -e
set -x

# MariaDB setup is handled by the "parent" image.

mv /usr/local/bin/healthcheck.sh /usr/local/bin/db_healthcheck.sh
mv /usr/local/bin/docker-entrypoint.sh /usr/local/bin/db_entrypoint.sh

cp /bd_build/mariadb/mariadb/db.sql /docker-entrypoint-initdb.d/00-azuracast.sql
cp /bd_build/mariadb/mariadb/db.cnf.tmpl /etc/mysql/db.cnf.tmpl
