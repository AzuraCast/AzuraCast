#!/bin/bash
set -e
set -x

curl -LsS https://r.mariadb.com/downloads/mariadb_repo_setup | bash -s -- \
  --mariadb-server-version=11.8.2

{ \
		echo "mariadb-server" mysql-server/root_password password 'unused'; \
		echo "mariadb-server" mysql-server/root_password_again password 'unused'; \
} | debconf-set-selections

apt-get update

apt-get install -q -y --no-install-recommends \
  mariadb-server mariadb-backup mariadb-client \
  ca-certificates gpg gpgv libjemalloc2 pwgen tzdata xz-utils zstd

rm -rf /var/lib/mysql
mkdir -p /var/lib/mysql /var/run/mysqld
chown -R mysql:mysql /var/lib/mysql /var/run/mysqld

# ensure that /var/run/mysqld (used for socket and lock files) is writable regardless of the UID our mysqld instance ends up having at runtime
chmod 777 /var/run/mysqld

# comment out a few problematic configuration values
find /etc/mysql/ -name '*.cnf' -print0 \
  | xargs -0 grep -lZE '^(bind-address|log|user\s)' \
  | xargs -rt -0 sed -Ei 's/^(bind-address|log|user\s)/#&/';

# don't reverse lookup hostnames, they are usually another container
printf "[mariadb]\nhost-cache-size=0\nskip-name-resolve\n" > /etc/mysql/mariadb.conf.d/05-skipcache.cnf;

mkdir /docker-entrypoint-initdb.d

cp /bd_build/mariadb/mariadb/db.sql /docker-entrypoint-initdb.d/00-azuracast.sql
cp /bd_build/mariadb/mariadb/db.cnf.tmpl /etc/mysql/db.cnf.tmpl
