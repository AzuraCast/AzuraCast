#!/usr/bin/env bash

BACKUP_FILENAME=${1:-"backup.tar.gz"}

docker-compose down

docker run --rm -v $(pwd):/backup \
    -v azuracast_db_data:/azuracast/db \
    -v azuracast_influx_data:/azuracast/influx \
    -v azuracast_station_data:/azuracast/stations \
    busybox tar zcvf /backup/$BACKUP_FILENAME /azuracast

docker-compose up -d