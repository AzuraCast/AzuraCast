#!/usr/bin/env bash

#
# Usage:
# ./docker-backup.sh [/custom/backup/dir/custombackupname.tar.gz]
#

APP_BASE_DIR=$(pwd)

BACKUP_PATH=${1:-"./backup.tar.gz"}
BACKUP_DIR=$(cd `dirname "$BACKUP_PATH"` && pwd)
BACKUP_FILENAME=`basename "$BACKUP_PATH"`

cd $APP_BASE_DIR

docker-compose down

docker run --rm -v $BACKUP_DIR:/backup \
    -v azuracast_db_data:/azuracast/db \
    -v azuracast_influx_data:/azuracast/influx \
    -v azuracast_station_data:/azuracast/stations \
    busybox tar zcvf /backup/$BACKUP_FILENAME /azuracast

docker-compose up -d