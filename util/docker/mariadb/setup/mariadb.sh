#!/bin/bash
set -e
set -x

apt-get install apt-transport-https curl
curl -o /etc/apt/trusted.gpg.d/mariadb_release_signing_key.asc 'https://mariadb.org/mariadb_release_signing_key.asc'
echo 'deb https://mirrors.gigenet.com/mariadb/repo/10.9/ubuntu jammy main' >> /etc/apt/sources.list

apt-get update

{ \
		echo "mariadb-server" mysql-server/root_password password 'unused'; \
		echo "mariadb-server" mysql-server/root_password_again password 'unused'; \
} | debconf-set-selections

apt-get install -q -y --no-install-recommends mariadb-server mariadb-backup

# comment out a few problematic configuration values
find /etc/mysql/ -name '*.cnf' -print0 \
  | xargs -0 grep -lZE '^(bind-address|log|user\s)' \
  | xargs -rt -0 sed -Ei 's/^(bind-address|log|user\s)/#&/';

# don't reverse lookup hostnames, they are usually another container
printf "[mariadb]\nhost-cache-size=0\nskip-name-resolve\n" > /etc/mysql/mariadb.conf.d/05-skipcache.cnf;

mkdir /docker-entrypoint-initdb.d

cp /bd_build/mariadb/mariadb/db.sql /docker-entrypoint-initdb.d/00-azuracast.sql
cp /bd_build/mariadb/mariadb/db.cnf.tmpl /etc/mysql/db.cnf.tmpl
