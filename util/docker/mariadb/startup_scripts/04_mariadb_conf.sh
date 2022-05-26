#!/bin/bash

if [ ! -f /etc/supervisor/minimal.conf.d/mariadb.conf ]; then
    echo "MariaDB disabled. Skipping DB initialization..."
    exit 0
fi

dockerize -template /etc/mysql/db.cnf.tmpl:/etc/mysql/conf.d/db.cnf
