#!/usr/bin/env bash

# shopt -s expand_aliases
# alias docker-compose-local='docker-compose -f docker-compose.yml '

docker-compose kill
docker stop $(docker ps -a -q)
docker rm $(docker ps -a -q)
docker volume prune -f

docker-compose build
docker-compose run web azuracast_install --dev
docker-compose up -d