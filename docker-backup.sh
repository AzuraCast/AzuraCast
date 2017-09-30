#!/usr/bin/env bash

#
# Usage:
# ./docker-backup.sh [/custom/backup/dir/custombackupname.tar.gz]
#

BACKUP_PATH=${1:-"./backup.tar.gz"}

BACKUP_DIR=`dirname "$BACKUP_PATH"`
BACKUP_FILENAME=`basename "$BACKUP_PATH"`

docker-compose down

docker run --rm -v $BACKUP_DIR:/backup \
    -v azuracast_db_data:/azuracast/db \
    -v azuracast_influx_data:/azuracast/influx \
    -v azuracast_station_data:/azuracast/stations \
    busybox tar zcvf /backup/$BACKUP_FILENAME /azuracast

docker-compose up -d