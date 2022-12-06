#!/bin/bash
set -e
set -x

mkdir -p /var/azuracast/centrifugo
cp /bd_build/web/centrifugo/config.yaml.tmpl /var/azuracast/centrifugo/config.yaml.tmpl

