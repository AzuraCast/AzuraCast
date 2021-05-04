#!/bin/bash

# Duplicate php-worker runit script based on environment variable

echo "Additional workers currently disabled."
exit 0
# Temporarily disabled. Explanation:
# The current worker setup uses MariaDB as the underlying message queue implementation. Multiple workers on the same
# queues causes multiple process to have write locks on the same table of the same database at the same time, which
# can tend to create a condition where the processes "pile up" on each other, causing semaphore lock overflow issues
# that can bring down an installation rather easily.
#
# Disabling this script forces a single worker to run to process message queues.

echo "Adding $ADDITIONAL_MEDIA_SYNC_WORKER_COUNT additional workers"

for ((WORKER_NUMBER = 1; WORKER_NUMBER <= ADDITIONAL_MEDIA_SYNC_WORKER_COUNT; WORKER_NUMBER++)); do
    echo "Adding worker $WORKER_NUMBER..."
    cp -r /etc/service/php-worker "/etc/service/php-worker-${WORKER_NUMBER}"

    sed -i "s/app_worker_0/app_worker_${WORKER_NUMBER}/" "/etc/service/php-worker-${WORKER_NUMBER}/run"
done

echo "Done"
