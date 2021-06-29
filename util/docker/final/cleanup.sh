#!/bin/bash
set -e
source /bd_build_final/buildconfig
set -x

apt-get clean
rm -rf /var/lib/apt/lists/*

rm -rf /tmp/tmp*
