#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

mkdir -p /docker-entrypoint-initdb.d

cp /bd_build/mariadb/db.sql /docker-entrypoint-initdb.d/00-azuracast.sql
cp /bd_build/mariadb/db.cnf.tmpl /etc/mysql/db.cnf.tmpl

curl -sSL https://downloads.mariadb.com/MariaDB/mariadb_repo_setup | bash -s -- --mariadb-server-version="mariadb-10.5"

$minimal_apt_get_install mariadb-server mariadb-client
