#!/bin/bash
set -e
set -x

# Compile nginx and nginx-push-module
add-apt-repository -y -s ppa:nginx/stable # -s is important b/c it allows source code access

apt build-dep -y nginx
cd /tmp
apt source nginx

cd /tmp/nginx-*/debian/modules
git clone https://github.com/wandenberg/nginx-push-stream-module.git

cd /tmp/nginx-*/debian
patch -p0 < /bd_build/web/nginx/add_nginx_push_stream.patch

cd /tmp/nginx-* && dpkg-buildpackage -uc -b -j2

dpkg-deb -I /tmp/nginx-common_*.deb
dpkg -i /tmp/nginx-common_*.deb

rm -rf /tmp/*

apt-mark hold nginx

aptitude markauto $(apt-cache showsrc PACKAGE_NAME | sed -e '/Build-Depends/!d;s/Build-Depends: \|,\|([^)]*),*\|\[[^]]*\]//g')
apt-get autoremove

# Install nginx and configuration
cp /bd_build/web/nginx/proxy_params.conf /etc/nginx/proxy_params
cp /bd_build/web/nginx/nginx.conf.tmpl /etc/nginx/nginx.conf.tmpl
cp /bd_build/web/nginx/azuracast.conf.tmpl /etc/nginx/azuracast.conf.tmpl

mkdir -p /etc/nginx/azuracast.conf.d/

# Create nginx temp dirs
mkdir -p /tmp/app_nginx_client /tmp/app_fastcgi_temp
touch /tmp/app_nginx_client/.tmpreaper
touch /tmp/app_fastcgi_temp/.tmpreaper
chmod -R 777 /tmp/app_*
