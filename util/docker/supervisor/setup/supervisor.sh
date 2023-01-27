#!/bin/bash
set -e
set -x

apt-get install -y --no-install-recommends python3-minimal python3-pip
pip3 install --no-cache-dir setuptools supervisor \
    git+https://github.com/coderanger/supervisor-stdout

# apt-get install -y --no-install-recommends supervisor

mkdir -p /etc/supervisor
mkdir -p /etc/supervisor/full.conf.d/
mkdir -p /etc/supervisor/minimal.conf.d/

cp /bd_build/supervisor/supervisor/supervisord*.conf /etc/supervisor/
