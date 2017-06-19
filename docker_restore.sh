#!/usr/bin/env bash

docker-compose down

docker volume rm azuracast_db_data azuracast_influx_data azuracast_station_data
docker volume create azuracast_db_data
docker volume create azuracast_influx_data
docker volume create azuracast_station_data

docker run --rm -v $(pwd):/backup \
    -v azuracast_db_data:/azuracast/db \
    -v azuracast_influx_data:/azuracast/influx \
    -v azuracast_station_data:/azuracast/stations \
    busybox tar zxvf /backup/backup.tar.gz

docker-compose up -d