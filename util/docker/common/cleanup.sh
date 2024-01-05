#!/bin/bash
set -e
set -x

apt-get -y autoremove

apt-get clean
rm -rf /var/lib/apt/lists/*
rm -rf /tmp/tmp*

chmod -R a+x /usr/local/bin
chmod -R +x /etc/my_init.d
