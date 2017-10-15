#!/usr/bin/env bash

# PowerShell equivalent:
# function docker-compose-dev { docker-compose -f docker-compose.yml -f docker-compose.build.yml -f docker-compose.dev.yml $args }
# function docker-compose-build { docker-compose -f docker-compose.yml -f docker-compose.build.yml $args }

docker-compose -f docker-compose.yml -f docker-compose.dev.yml "$@"