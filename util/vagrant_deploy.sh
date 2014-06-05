#!/bin/bash

export app_base=/var/www
export tmp_base=$app_base/www_tmp
export www_base=$app_base/vagrant

if [ -f $app_base/.deploy_run ]
then
	echo 'One-time setup has already been done!'
	exit
fi

# Set up environment.
touch $app_base/.deploy_run
touch $www_base/app/.updated

echo 'development' > $app_base/app/.env

# Goodies for nerds. ;)
apt-get -q -y install vim
apt-get -q -y remove redis-server
apt-get -q -y remove mongodb-org
apt-get autoremove

# Create temp folders.
echo "Creating temporary folders..."
mkdir -p $tmp_base
mkdir -p $tmp_base/cache
mkdir -p $tmp_base/sessions
mkdir -p $tmp_base/proxies

# Create log files.
echo "Setting permissions..."
touch $tmp_base/access.log
touch $tmp_base/error.log
touch $tmp_base/php_errors.log
touch $tmp_base/vagrant_import.sql

usermod -G vagrant www-data
usermod -G vagrant nobody

chown -R vagrant:vagrant $tmp_base/

chmod -R 777 $tmp_base
# chmod -R 775 $www_base/web/static

# Service setup.
echo "Customizing nginx..."

service nginx stop

mv /etc/nginx/conf/nginx.conf /etc/nginx/conf/nginx.conf.bak
cp /vagrant/util/vagrant_nginx /etc/nginx/conf/nginx.conf

# Set up MySQL server.
echo "Customizing MySQL..."

cat $www_base/util/vagrant_mycnf >> /etc/mysql/my.cnf

echo 'CREATE DATABASE pvl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;' | mysql -u root -ppassword
service mysql restart

# Copy sample files.
if [ ! -f $www_base/app/config/apis.conf.php ]
then
	cp $www_base/app/config/apis.conf.sample.php $www_base/app/config/apis.conf.php
fi

if [ ! -f $www_base/app/config/db.conf.php ]
then
	cp $www_base/app/config/db.conf.sample.php $www_base/app/config/db.conf.php
fi

# Install PHP-CLI
echo "Installing PHP5 Command Line Interface (CLI)..."

apt-get -q -y install php5-cli

echo "alias phpwww='sudo -u www-data php'" >> /home/vagrant/.profile

# Trigger mlocate reindex.
updatedb

# Enable PHP flags.
sed -e '/^[^;]*short_open_tag/s/=.*$/= On/' -i.bak /etc/php5/fpm/php.ini
sed -e '/^[^;]*short_open_tag/s/=.*$/= On/' -i.bak /etc/php5/cli/php.ini

service php5-fpm restart

# Install composer.
echo "Installing Composer..."
cd /root
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

if [ ! -f $www_base/vendor/autoload.php ]
then
	cd $www_base
	composer install
fi

# Set up DB.
echo "Setting up database..."

cd $www_base/util
sudo -u www-data php doctrine.php orm:schema-tool:create
sudo -u www-data php flush.php
sudo -u www-data php vagrant_import.php

echo "Importing external music databases (takes a minute)..."
sudo -u www-data php syncslow.php

echo "Running regular tasks..."
sudo -u www-data php syncfast.php
sudo -u www-data php sync.php

sudo -u www-data php nowplaying.php

# Add cron job
echo "Installing cron job..."
crontab -u vagrant $www_base/util/vagrant_cron
service cron restart

service nginx start

echo "One-time setup complete!"
echo "Server now live at localhost:8080 or www.pvlive.dev:8080."