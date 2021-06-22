#!/bin/bash
set -e
source /bd_build_final/buildconfig
set -x

# $minimal_apt_get_install python3-minimal python3-pip
# pip3 install setuptools supervisor

$minimal_apt_get_install supervisor

# mkdir -p /etc/supervisor
cp /bd_build_final/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
