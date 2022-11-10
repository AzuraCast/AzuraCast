#!/bin/bash

# Copy the nginx template to its destination.
dockerize -template "/etc/nginx/nginx.conf.tmpl:/etc/nginx/nginx.conf" \
    -template "/etc/nginx/azuracast.conf.tmpl:/etc/nginx/sites-available/default"
