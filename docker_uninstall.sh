#!/usr/bin/env bash

read -p "This operation is destructive and will wipe your existing Docker containers. Continue? [y/N] " -n 1 -r
echo

if [[ $REPLY =~ ^[Yy]$ ]]; then

    docker-compose down -v
    docker stop $(docker ps -a -q)
    docker rm $(docker ps -a -q)
    docker volume prune -f

fi