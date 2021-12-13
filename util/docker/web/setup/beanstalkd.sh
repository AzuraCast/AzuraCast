#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

export RUNLEVEL=1

$minimal_apt_get_install beanstalkd
