#!/bin/bash

# Duplicate php-worker runit script based on environment variable

echo "Adding $ADDITIONAL_MEDIA_SYNC_WORKER_COUNT additional workers"

for ((WORKER_NUMBER = 1; WORKER_NUMBER <= ADDITIONAL_MEDIA_SYNC_WORKER_COUNT; WORKER_NUMBER++)); do
    echo "Adding worker $WORKER_NUMBER..."
    cp -r /etc/service/php-worker "/etc/service/php-worker-${WORKER_NUMBER}"

    sed -i "s/app_worker_0/app_worker_${WORKER_NUMBER}/" "/etc/service/php-worker-${WORKER_NUMBER}/run"
done

echo "Done"
