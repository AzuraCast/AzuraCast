#!/bin/bash

if [[ ! -f /var/azuracast/storage/sftpgo/id_rsa ]]; then
    ssh-keygen -t rsa -b 4096 -f /var/azuracast/storage/sftpgo/id_rsa -q -N ""
fi

if [[ ! -f /var/azuracast/storage/sftpgo/id_ecdsa ]]; then
    ssh-keygen -t ecdsa -b 521 -f /var/azuracast/storage/sftpgo/id_ecdsa -q -N ""
fi

if [[ ! -f /var/azuracast/storage/sftpgo/id_ed25519 ]]; then
    ssh-keygen -t ed25519 -f /var/azuracast/storage/sftpgo/id_ed25519 -q -N ""
fi

chown -R azuracast:azuracast /var/azuracast/storage/sftpgo
