#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

add-apt-repository -y ppa:chris-needham/ppa
apt-get update

$minimal_apt_get_install audiowaveform
