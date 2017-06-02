#!/usr/bin/env bash

shopt -s expand_aliases
alias docker-compose-local='docker-compose -f docker-compose.yml -f docker-compose.build.yml '

docker-compose-local down -v
docker stop $(docker ps -a -q)
docker rm $(docker ps -a -q)
docker volume prune -f

docker-compose-local build
docker-compose-local run web azuracast_install --dev
docker-compose-local up -d