#!/bin/bash

AZURACAST_PUID="${AZURACAST_PUID:-1000}"
AZURACAST_PGID="${AZURACAST_PGID:-1000}"

PUID="${PUID:-$AZURACAST_PUID}"
PGID="${PGID:-$AZURACAST_PGID}"

groupmod -o -g "$PGID" azuracast
usermod -o -u "$PUID" azuracast

echo "Docker 'azuracast' User UID: $(id -u azuracast)"
echo "Docker 'azuracast' User GID: $(id -g azuracast)"
