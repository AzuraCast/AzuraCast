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

if [ $APP_ENV = "production" ]; then

    docker-compose pull
    docker-compose run --rm cli azuracast_install
    docker-compose up -d

else

    read -p "This operation is destructive and will wipe your existing Docker containers. Continue? [y/N] " -n 1 -r
    echo

    if [[ $REPLY =~ ^[Yy]$ ]]; then

        docker-compose -f docker-compose.yml -f docker-compose.build.yml -f docker-compose.dev.yml down -v
        docker stop $(docker ps -a -q)
        docker rm $(docker ps -a -q)
        docker volume prune -f

        docker-compose -f docker-compose.yml -f docker-compose.build.yml -f docker-compose.dev.yml build
        docker-compose -f docker-compose.yml -f docker-compose.build.yml -f docker-compose.dev.yml run --rm cli azuracast_install --dev
        docker-compose -f docker-compose.yml -f docker-compose.build.yml -f docker-compose.dev.yml up -d
        docker-compose -f docker-compose.yml -f docker-compose.build.yml -f docker-compose.dev.yml rm -f

    fi

fi
