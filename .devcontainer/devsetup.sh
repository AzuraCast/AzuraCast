#!/usr/bin/env bash

cp dev.env .env
cp azuracast.dev.env azuracast.env

cp docker-compose.cloudide.yml docker-compose.yml

docker-compose build web
docker-compose run --rm --user=azuracast web azuracast_install
docker-compose up -d
