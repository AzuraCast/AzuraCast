#!/bin/bash

ENABLE_REDIS=${ENABLE_REDIS:-true}
export ENABLE_REDIS

dockerize -template "/var/azuracast/centrifugo/config.yaml.tmpl:/var/azuracast/centrifugo/config.yaml"
