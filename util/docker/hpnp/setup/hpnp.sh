#!/bin/bash
set -e
set -x

apt-get install -y --no-install-recommends nodejs



apt-get remove --purge -y nodejs
