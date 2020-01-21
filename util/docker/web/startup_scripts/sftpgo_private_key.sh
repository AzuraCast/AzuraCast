#!/bin/bash

if [[ ! -f /var/azuracast/sftpgo/persist/id_rsa ]]; then
    ssh-keygen -t rsa -b 4096 -f /var/azuracast/sftpgo/persist/id_rsa -q -N ""
fi

chown -R azuracast:azuracast /var/azuracast/sftpgo/persist