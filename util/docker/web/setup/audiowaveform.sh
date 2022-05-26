#!/bin/bash
set -e
set -x

add-apt-repository -y ppa:chris-needham/ppa
apt-get update

apt-get install -y --no-install-recommends audiowaveform
