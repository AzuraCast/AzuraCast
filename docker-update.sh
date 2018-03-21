#!/usr/bin/env bash

while test $# -gt 0; do
    case "$1" in
        --dev)
            APP_ENV="development"
            shift
            ;;
    esac
done

APP_ENV="${APP_ENV:-production}"

docker-compose down
docker-compose rm -f
docker-compose pull

if [ $APP_ENV = "production" ]; then
    docker-compose run --rm cli azuracast_update
else
    docker-compose run --rm cli azuracast_update --dev
fi

docker-compose up -d

docker rmi $(docker images | grep "none" | awk '/ / { print $3 }')