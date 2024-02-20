#!/bin/bash

if [ ! -f /etc/supervisor/minimal.conf.d/mariadb.conf ]; then
    echo "MariaDB disabled. Skipping DB initialization..."
    exit 0
fi

export MARIADB_AUTO_UPGRADE=1

source /usr/local/bin/db_entrypoint.sh

set -- mariadbd

mysql_note "Initial DB setup..."

mysql_check_config "$@"
# Load various environment variables
docker_setup_env "$@"

# Create DB directories
mkdir -p "$DATADIR"
if [ "$(id -u)" = "0" ]; then
  # this will cause less disk access than `chown -R`
  find "$DATADIR" \! -user mysql -exec chown mysql: '{}' +
  # See https://github.com/MariaDB/mariadb-docker/issues/363
  find "${SOCKET%/*}" -maxdepth 0 \! -user mysql -exec chown mysql: '{}' \;
fi

# If container is started as root user, restart as dedicated mysql user
if [ "$(id -u)" = "0" ]; then
    mysql_note "Switching to dedicated user 'mysql'"
    exec gosu mysql "${BASH_SOURCE[0]}" "$@"
fi

# there's no database, so it needs to be initialized
if [ -z "$DATABASE_ALREADY_EXISTS" ]; then
    docker_verify_minimum_env
    docker_mariadb_init "$@"
# MDEV-27636 mariadb_upgrade --check-if-upgrade-is-needed cannot be run offline
#elif mysql_upgrade --check-if-upgrade-is-needed; then
elif _check_if_upgrade_is_needed; then
    docker_mariadb_upgrade "$@"
fi
