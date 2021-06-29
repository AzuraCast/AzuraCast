#!/bin/bash

PUID=${PUID:-1000}
PGID=${PGID:-1000}

groupmod -o -g "$PGID" azuracast
usermod -o -u "$PUID" azuracast

echo "Docker 'azuracast' User UID: $(id -u azuracast)"
echo "Docker 'azuracast' User GID: $(id -g azuracast)"
