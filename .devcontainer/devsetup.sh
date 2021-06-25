#!/usr/bin/env bash

cp sample.env .env
cp azuracast.dev.env azuracast.env
cp docker-compose.sample.yml docker-compose.yml
cp .devcontainer/docker-compose.override.yml docker-compose.override.yml
docker-compose build web
docker-compose run --rm --user=azuracast web azuracast_install
docker-compose up -d