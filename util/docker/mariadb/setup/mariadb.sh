#!/bin/bash
set -e
set -x

apt-get install -y --no-install-recommends tzdata libjemalloc2 pwgen xz-utils zstd dirmngr apt-transport-https

sudo apt-key adv --fetch-keys 'https://mariadb.org/mariadb_release_signing_key.asc'
sudo add-apt-repository 'deb [arch=amd64,arm64,ppc64el,s390x] https://atl.mirrors.knownhost.com/mariadb/repo/10.7/ubuntu focal main'

# Pulled from MariaDB Docker container
export MARIADB_MAJOR=10.7

{
  echo "mariadb-server-$MARIADB_MAJOR" mysql-server/root_password password 'unused';
	echo "mariadb-server-$MARIADB_MAJOR" mysql-server/root_password_again password 'unused';
} | debconf-set-selections;

apt update
apt-get install -y --no-install-recommends mariadb-server mariadb-backup socat

# Temporary work around for MDEV-27980, closes #417
sed --follow-symlinks -i -e 's/--loose-disable-plugin-file-key-management//' /usr/bin/mysql_install_db

# Purge and re-create /var/lib/mysql with appropriate ownership
rm -rf /var/lib/mysql;
mkdir -p /var/lib/mysql /var/run/mysqld;
chown -R mysql:mysql /var/lib/mysql /var/run/mysqld;

# ensure that /var/run/mysqld (used for socket and lock files) is writable regardless of the UID our mysqld instance ends up having at runtime
chmod 777 /var/run/mysqld;

# comment out a few problematic configuration values
find /etc/mysql/ -name '*.cnf' -print0 \
  | xargs -0 grep -lZE '^(bind-address|log|user\s)' \
  | xargs -rt -0 sed -Ei 's/^(bind-address|log|user\s)/#&/';

# don't reverse lookup hostnames, they are usually another container
# Issue #327 Correct order of reading directories /etc/mysql/mariadb.conf.d before /etc/mysql/conf.d (mount-point per documentation)
if [ ! -L /etc/mysql/my.cnf ]; then
  sed -i -e '/includedir/i[mariadb]\nskip-host-cache\nskip-name-resolve\n' /etc/mysql/my.cnf;
else
  sed -i -e '/includedir/ {N;s/\(.*\)\n\(.*\)/[mariadbd]\nskip-host-cache\nskip-name-resolve\n\n\2\n\1/}' /etc/mysql/mariadb.cnf;
fi

mkdir /docker-entrypoint-initdb.d

cp /bd_build/mariadb/mariadb/db.sql /docker-entrypoint-initdb.d/00-azuracast.sql
cp /bd_build/mariadb/mariadb/db.cnf.tmpl /etc/mysql/db.cnf.tmpl
