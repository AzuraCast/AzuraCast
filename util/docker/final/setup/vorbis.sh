#!/bin/bash
set -e
source /bd_build_final/buildconfig
set -x

$minimal_apt_get_install vorbis-tools
