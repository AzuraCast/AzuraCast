#!/bin/bash

# Copy the nginx template to its destination.
dockerize -template "/etc/nginx/azuracast.conf.tmpl:/etc/nginx/conf.d/azuracast.conf"
