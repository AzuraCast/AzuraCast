#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

MARIADB_VERSION="10.5"

echo "${MARIADB_VERSION}" >> /etc/container_environment/MARIADB_VERSION
echo "1" >> /etc/container_environment/MARIADB_AUTO_UPGRADE

curl -sSL https://downloads.mariadb.com/MariaDB/mariadb_repo_setup | bash -s -- --mariadb-server-version="mariadb-${MARIADB_VERSION}"

$minimal_apt_get_install mariadb-server mariadb-client

mkdir -p /docker-entrypoint-initdb.d

cp /bd_build/mariadb/mariadb/db.sql /docker-entrypoint-initdb.d/00-azuracast.sql
cp /bd_build/mariadb/mariadb/db.cnf.tmpl /etc/mysql/db.cnf.tmpl
