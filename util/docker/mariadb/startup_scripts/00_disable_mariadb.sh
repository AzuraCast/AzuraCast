#!/bin/bash

# If the MariaDB host is anything but localhost, disable MariaDB on this container.
if [ "$MYSQL_HOST" != "localhost" ]; then
    echo "MariaDB host is not localhost; disabling MariaDB..."

    rm -rf /etc/service/mariadb
    rm -rf /etc/service.minimal/mariadb
fi
