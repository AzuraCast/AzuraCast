#!/usr/bin/env bash

read -p "This operation is destructive and will wipe your existing Docker containers. Continue? [y/N] " -n 1 -r
echo

if [[ $REPLY =~ ^[Yy]$ ]]; then

    docker-compose -f docker-compose.yml -f docker-compose.dev.yml down -v
    docker stop $(docker ps -a -q)
    docker rm $(docker ps -a -q)
    docker volume prune -f

    docker-compose -f docker-compose.yml -f docker-compose.dev.yml build
    docker-compose -f docker-compose.yml -f docker-compose.dev.yml run --rm cli azuracast_install --dev
    docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
    docker-compose -f docker-compose.yml -f docker-compose.dev.yml rm -f

fi