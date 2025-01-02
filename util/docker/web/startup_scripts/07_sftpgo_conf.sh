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

echo "SFTPGO_SFTPD__BINDINGS__0__PORT=${AZURACAST_SFTP_PORT:-2022}" > /var/azuracast/sftpgo/env.d/sftpd.env

chown -R azuracast:azuracast /var/azuracast/storage/sftpgo
