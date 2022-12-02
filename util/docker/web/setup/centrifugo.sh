#!/bin/bash
set -e
set -x

mkdir -p /var/azuracast/centrifugo
cp /bd_build/web/centrifugo/config.json /var/azuracast/centrifugo/config.json

