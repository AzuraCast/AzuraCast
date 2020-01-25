#!/usr/bin/env bash

if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root"
   exit 1
fi

# Run system update first
if [ ! -f ./docker-compose.yml ]; then
    cp ./docker-compose.sample.yml ./docker-compose.yml
fi
if [ ! -f ./azuracast.env ]; then
    cp ./azuracast.sample.env ./azuracast.env
fi

BASE_DIR=`pwd`

# Create backup from existing installation.
chmod a+x bin/console
./bin/console azuracast:backup --exclude-media migration.zip

read -n 1 -s -r -p "Database backed up. Press any key to continue (Install Docker)..."

# Install Docker
wget -qO- https://get.docker.com/ | sh

COMPOSE_VERSION=`git ls-remote https://github.com/docker/compose | grep refs/tags | grep -oP "[0-9]+\.[0-9][0-9]+\.[0-9]+$" | tail -n 1`
sudo sh -c "curl -L https://github.com/docker/compose/releases/download/${COMPOSE_VERSION}/docker-compose-`uname -s`-`uname -m` > /usr/local/bin/docker-compose"
sudo chmod +x /usr/local/bin/docker-compose
sudo sh -c "curl -L https://raw.githubusercontent.com/docker/compose/${COMPOSE_VERSION}/contrib/completion/bash/docker-compose > /etc/bash_completion.d/docker-compose"

# Pull Docker images
read -n 1 -s -r -p "Docker installed. Press any key to continue (Uninstall Ansible AzuraCast)..."

# Run Ansible uninstaller
chmod a+x uninstall.sh
./uninstall.sh

read -n 1 -s -r -p "Uninstall complete. Press any key to continue (Install AzuraCast in Docker)..."

# Copy override file.
cp docker-compose.migrate.yml docker-compose.override.yml

# Spin up Docker
docker-compose pull
sleep 5

# Run restore op
chmod a+x docker.sh

# Set appropriate permissions on the stations directory
chown -R 1000 /var/azuracast/stations

docker-compose run --rm --user="azuracast" web azuracast_restore migration.zip
docker-compose up -d

read -n 1 -s -r -p "Docker is running. Press any key to continue (cleanup)..."

# Codebase cleanup
find -maxdepth 1 ! -name . ! -name docker-compose.yml ! -name docker-compose.override.yml \
     ! -name docker.sh ! -name .env ! -name azuracast.env ! -name plugins \
     -exec rm -rv {} \;
