#!/usr/bin/env bash

./docker-compose-dev.sh down -v
docker stop $(docker ps -a -q)
docker rm $(docker ps -a -q)
docker volume prune -f

./docker-compose-dev.sh build
./docker-compose-dev.sh run --rm cli azuracast_install --dev
./docker-compose-dev.sh up -d
./docker-compose-dev.sh rm -f