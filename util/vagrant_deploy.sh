#!/bin/bash

if [ -f /var/www/.deploy_run ]
then
	echo 'One-time setup has already been done!'
	exit
fi

touch /var/www/.deploy_run
export DEBIAN_FRONTEND=noninteractive

# Goodies for nerds.
apt-get -q -y install vim

# Create temp folders.
mkdir -p /var/www/www_tmp
mkdir -p /var/www/www_tmp/cache
mkdir -p /var/www/www_tmp/sessions
mkdir -p /var/www/vagrant/app/models/Proxy

# Create log files.
touch /var/www/www_tmp/access.log
touch /var/www/www_tmp/error.log
touch /var/www/www_tmp/php_errors.log

usermod -G vagrant www-data
usermod -G vagrant nobody

chown -R vagrant:vagrant /var/www/www_tmp/

chmod -R 777 /var/www/www_tmp
chmod -R 777 /var/www/vagrant/app/models/Proxy
chmod -R 775 /var/www/vagrant/web/static

# Service setup.
service nginx stop

mv /etc/nginx/conf/nginx.conf /etc/nginx/conf/nginx.conf.bak
cp /vagrant/util/vagrant_nginx /etc/nginx/conf/nginx.conf

service nginx start

# Set up MySQL server.
echo 'CREATE DATABASE pvl;' | mysql -u root -ppassword
service mysql restart

# Copy sample files.
if [ ! -f /var/www/vagrant/app/config/apis.conf.php ]
then
	cp /var/www/vagrant/app/config/apis.conf.sample.php /var/www/vagrant/app/config/apis.conf.php
fi

if [ ! -f /var/www/vagrant/app/config/db.conf.php ]
then
	cp /var/www/vagrant/app/config/db.conf.sample.php /var/www/vagrant/app/config/db.conf.php
fi

# Install PHP-CLI
apt-get -q -y install php5-cli

echo "alias phpwww='sudo -u www-data php'" >> /home/vagrant/.profile

# Trigger mlocate reindex.
updatedb

# Enable PHP flags.
sed -e '/^[^;]*short_open_tag/s/=.*$/= On/' -i.bak /etc/php5/fpm/php.ini
sed -e '/^[^;]*short_open_tag/s/=.*$/= On/' -i.bak /etc/php5/cli/php.ini

service php5-fpm restart

# Install composer.
cd /root
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Set up DB.
cd /var/www/vagrant/util
sudo -u www-data php doctrine.php orm:schema-tool:create
sudo -u www-data php flush.php

# Add cron job
crontab /var/www/vagrant/util/vagrant_cron