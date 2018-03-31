#!/usr/bin/env bash

docker-compose down
docker-compose rm -f

read -p "Update docker-compose.yml file? This will overwrite any customizations you made to this file. [y/N] " -n 1 -r
echo

if [[ $REPLY =~ ^[Yy]$ ]]; then

    cp docker-compose.yml docker-compose.backup.yml
    echo "Your existing docker-compose.yml file has been backed up to docker-compose.backup.yml."

    curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker-compose.sample.yml > docker-compose.yml
    echo "New docker-compose.yml file loaded."

fi

docker-compose pull
docker-compose run --rm cli azuracast_update
docker-compose up -d

docker rmi $(docker images | grep "none" | awk '/ / { print $3 }')