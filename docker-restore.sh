#!/usr/bin/env bash

#
# Usage:
# ./docker-restore.sh [/custom/backup/dir/custombackupname.tar.gz]
#

APP_BASE_DIR=$(pwd)

BACKUP_PATH=${1:-"./backup.tar.gz"}
BACKUP_DIR=$(cd `dirname "$BACKUP_PATH"` && pwd)
BACKUP_FILENAME=`basename "$BACKUP_PATH"`

cd $APP_BASE_DIR

if [ -f $BACKUP_PATH ]; then
   docker-compose down

    docker volume rm azuracast_db_data azuracast_influx_data azuracast_station_data
    docker volume create azuracast_db_data
    docker volume create azuracast_influx_data
    docker volume create azuracast_station_data

    docker run --rm -v $BACKUP_DIR:/backup \
        -v azuracast_db_data:/azuracast/db \
        -v azuracast_influx_data:/azuracast/influx \
        -v azuracast_station_data:/azuracast/stations \
        busybox tar zxvf /backup/$BACKUP_FILENAME

    docker-compose up -d
else
    echo "File $BACKUP_PATH does not exist in this directory. Nothing to restore."
    exit 1
fi