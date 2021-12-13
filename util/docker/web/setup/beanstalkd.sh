#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

$minimal_apt_get_install netbase

install_without_postinst beanstalkd
