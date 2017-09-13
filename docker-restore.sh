#!/usr/bin/env bash

BACKUP_FILENAME=${1:-"backup.tar.gz"}

if [ -f $BACKUP_FILENAME ]; then
   docker-compose down

    docker volume rm azuracast_db_data azuracast_influx_data azuracast_station_data
    docker volume create azuracast_db_data
    docker volume create azuracast_influx_data
    docker volume create azuracast_station_data

    docker run --rm -v $(pwd):/backup \
        -v azuracast_db_data:/azuracast/db \
        -v azuracast_influx_data:/azuracast/influx \
        -v azuracast_station_data:/azuracast/stations \
        busybox tar zxvf /backup/$BACKUP_FILENAME

    docker-compose up -d
else
    echo "File $BACKUP_FILENAME does not exist in this directory. Nothing to restore."
fi