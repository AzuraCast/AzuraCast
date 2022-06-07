#!/bin/bash
set -e
set -x

apt-get -y autoremove

apt-get clean
rm -rf /var/lib/apt/lists/*
rm -rf /tmp/tmp*
