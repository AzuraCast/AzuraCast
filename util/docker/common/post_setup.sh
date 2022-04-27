#!/bin/bash
set -e
source /bd_build/buildconfig
set -x

chmod -R a+x /usr/local/bin
chmod -R +x /etc/my_init.d
chmod -R +x /etc/my_init.pre_shutdown.d
chmod -R +x /etc/my_init.post_shutdown.d

ln -s /etc/service.minimal/* /etc/service
ln -s /etc/service.full/* /etc/service

chmod -R +x /etc/service.minimal
chmod -R +x /etc/service.full
chmod -R +x /etc/service
