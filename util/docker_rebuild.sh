#!/usr/bin/env bash

docker-compose kill
docker stop $(docker ps -a -q)
docker rm $(docker ps -a -q)
docker volume prune -f

docker-compose up -d --build
docker-compose run web azuracast_install --dev