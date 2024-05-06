#!/bin/bash

ENABLE_REDIS=${ENABLE_REDIS:-true}
export ENABLE_REDIS

dockerize -template "/var/azuracast/centrifugo/config.toml.tmpl:/var/azuracast/centrifugo/config.toml"
