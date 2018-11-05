#!/usr/bin/env bash

if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root"
   exit 1
fi

# Run system update first
chmod a+x update.sh
./update.sh

if [ ! -f ./docker-compose.yml ]; then
    cp ./docker-compose.sample.yml ./docker-compose.yml
fi
if [ ! -f ./azuracast.env ]; then
    cp ./azuracast.sample.env ./azuracast.env
fi

BASE_DIR=`pwd`

mkdir -p ${BASE_DIR}/migration
mkdir -p ${BASE_DIR}/migration/influxdb

# Dump MySQL data into fixtures folder
MYSQL_USERNAME=`awk -F "=" '/db_username/ {print $2}' env.ini | tr -d ' '`
MYSQL_PASSWORD=`awk -F "=" '/db_password/ {print $2}' env.ini | tr -d ' '`

mysqldump --add-drop-table -u$MYSQL_USERNAME -p$MYSQL_PASSWORD azuracast > migration/database.sql

read -n 1 -s -r -p "MySQL exported. Press any key to continue (Export InfluxDB)..."

# Dump InfluxDB data
mkdir -p /var/azuracast/migration

influxd backup -database stations ${BASE_DIR}/migration/influxdb

read -n 1 -s -r -p "InfluxDB exported. Press any key to continue (Install Docker)..."

# Install Docker
wget -qO- https://get.docker.com/ | sh

COMPOSE_VERSION=`git ls-remote https://github.com/docker/compose | grep refs/tags | grep -oP "[0-9]+\.[0-9][0-9]+\.[0-9]+$" | tail -n 1`
sudo sh -c "curl -L https://github.com/docker/compose/releases/download/${COMPOSE_VERSION}/docker-compose-`uname -s`-`uname -m` > /usr/local/bin/docker-compose"
sudo chmod +x /usr/local/bin/docker-compose
sudo sh -c "curl -L https://raw.githubusercontent.com/docker/compose/${COMPOSE_VERSION}/contrib/completion/bash/docker-compose > /etc/bash_completion.d/docker-compose"

# Pull Docker images
read -n 1 -s -r -p "Docker installed. Press any key to continue (Uninstall Traditional AzuraCast)..."

# Run traditional uninstaller
chmod a+x uninstall.sh
./uninstall.sh

read -n 1 -s -r -p "Uninstall complete. Press any key to continue (Install AzuraCast in Docker)..."

# Spin up Docker
docker-compose pull
docker-compose -f docker-compose.yml -f docker-compose.migrate.yml up -d

# Run Docker AzuraCast-specific installer
docker-compose -f docker-compose.yml -f docker-compose.migrate.yml run --rm influxdb import_folder /tmp/migration
docker-compose -f docker-compose.yml -f docker-compose.migrate.yml exec mariadb import_file /tmp/database.sql
docker-compose -f docker-compose.yml -f docker-compose.migrate.yml run --rm cli azuracast_migrate_stations /tmp/migration
docker-compose -f docker-compose.yml -f docker-compose.migrate.yml run --rm cli azuracast_install

docker-compose -f docker-compose.yml -f docker-compose.migrate.yml down
docker-compose up -d

# Docker cleanup
docker-compose rm -f

docker volume prune -f
docker rmi $(docker images | grep "none" | awk '/ / { print $3 }')

# Codebase cleanup
rm -rf /var/azuracast/www_tmp
rm -rf /var/azuracast/stations
rm -rf /var/azuracast/servers

find -maxdepth 1 ! -name migration ! -name . ! -name docker-compose.yml \
     ! -name .env ! -name azuracast.env ! -name plugins \
     -exec rm -rv {} \;
