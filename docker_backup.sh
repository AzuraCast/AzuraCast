#!/usr/bin/env bash

docker-compose down

# Back up stations
rm -f backup.tar.gz

docker run --rm -v $(pwd):/backup \
    -v azuracast_db_data:/azuracast/db \
    -v azuracast_influx_data:/azuracast/influx \
    -v azuracast_station_data:/azuracast/stations \
    busybox tar zcvf /backup/backup.tar.gz /azuracast

docker-compose up -d