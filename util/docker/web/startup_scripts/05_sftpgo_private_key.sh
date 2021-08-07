#!/bin/bash

if [[ ! -f /var/azuracast/sftpgo/persist/id_rsa ]]; then
    ssh-keygen -t rsa -b 4096 -f /var/azuracast/sftpgo/persist/id_rsa -q -N ""
fi

if [[ ! -f /var/azuracast/sftpgo/persist/id_ecdsa ]]; then
    ssh-keygen -t ecdsa -b 521 -f /var/azuracast/sftpgo/persist/id_ecdsa -q -N ""
fi

if [[ ! -f /var/azuracast/sftpgo/persist/id_ed25519 ]]; then
    ssh-keygen -t ed25519 -f /var/azuracast/sftpgo/persist/id_ed25519 -q -N ""
fi

chown -R azuracast:azuracast /var/azuracast/sftpgo/persist
