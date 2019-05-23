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

# Create backup from existing installation.
chmod a+x bin/azuracast
./bin/azuracast azuracast:backup --exclude-media ./migration.tar.gz

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

# Spin up Docker
docker-compose pull
sleep 5

# Copy media.
docker-compose run --user="azuracast" --rm \
    -v /var/azuracast/stations:/tmp/migration \
    mv /tmp/migration/* /var/azuracast/stations

# Copy all other settings.
chmod a+x docker.sh
./docker.sh restore ./migration.tar.gz

read -n 1 -s -r -p "Docker is running. Press any key to continue (cleanup)..."

# Codebase cleanup
rm -rf /var/azuracast/stations

find -maxdepth 1 ! -name migration.tar.gz ! -name . ! -name docker-compose.yml \
     ! -name docker.sh ! -name .env ! -name azuracast.env ! -name plugins \
     -exec rm -rv {} \;
