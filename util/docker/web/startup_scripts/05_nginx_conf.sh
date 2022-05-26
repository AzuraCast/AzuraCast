#!/bin/bash

REDIS_LOCAL=false
if [ -f /etc/supervisor/minimal.conf.d/redis.conf ]; then
    REDIS_LOCAL=true
fi
export REDIS_LOCAL

# Copy the nginx template to its destination.
dockerize -template "/etc/nginx/nginx.conf.tmpl:/etc/nginx/nginx.conf" \
    -template "/etc/nginx/azuracast.conf.tmpl:/etc/nginx/sites-available/default"
