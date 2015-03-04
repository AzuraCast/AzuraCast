#!/bin/bash

export DEBIAN_FRONTEND=noninteractive
export app_base=/var/www
export tmp_base=$app_base/www_tmp
export www_base=$app_base/vagrant

if [ ! -f $app_base/.deploy_run ]
then

    # Set up swap partition
    sudo fallocate -l 2G /swapfile
    sudo chmod 600 /swapfile
    sudo mkswap /swapfile
    sudo swapon /swapfile
    sudo echo "/swapfile   none    swap    sw    0   0" >> /etc/fstab

    # Set up server
    apt-get -q -y install python-software-properties
    apt-add-repository ppa:phalcon/stable
    apt-get update

    apt-get -q -y install vim git nginx mysql-server-5.6 php5-fpm php5-cli php5-gd php5-mysql php5-curl php5-phalcon
    apt-get autoremove

    mysqladmin -u root password password

    # Trigger mlocate reindex.
    updatedb

    # Set up environment.
    touch $www_base/app/.updated

    echo 'development' > $app_base/app/.env

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
    usermod -G www-data vagrant

    chown -R vagrant:vagrant $tmp_base/

    chmod -R 777 $tmp_base

    # Nginx setup.
    echo "Customizing nginx..."

    service nginx stop

    mv /etc/nginx/nginx.conf /etc/nginx/nginx.conf.bak
    cp /vagrant/util/vagrant_nginx /etc/nginx/nginx.conf

    chown -R vagrant /var/log/nginx

    unlink /etc/nginx/sites-enabled/default

    # Set up MySQL server.
    echo "Customizing MySQL..."

    cat $www_base/util/vagrant_mycnf >> /etc/mysql/my.cnf

    echo "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY 'password' WITH GRANT OPTION;" | mysql -u root -ppassword
    echo "CREATE DATABASE pvl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | mysql -u root -ppassword
    service mysql restart

    # Enable PHP flags.
    echo "alias phpwww='sudo -u vagrant php'" >> /home/vagrant/.profile

    sed -e '/^[^;]*short_open_tag/s/=.*$/= On/' -i.bak /etc/php5/fpm/php.ini
    sed -e '/^[^;]*short_open_tag/s/=.*$/= On/' -i.bak /etc/php5/cli/php.ini

    mv /etc/php5/fpm/pool.d/www.conf /etc/php5/fpm/www.conf.bak
    cp /vagrant/util/vagrant_phpfpm.conf /etc/php5/fpm/pool.d/www.conf

    service php5-fpm restart

    # Install composer.
    echo "Installing Composer..."
    cd /root
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer

    # Install Node.js and services
    apt-get -q -y install nodejs npm

    cd $www_base/live
    npm install --no-bin-links

    cp $www_base/util/vagrant_initd /etc/init/pvlnode.conf

    # Mark deployment as run.
    touch $app_base/.deploy_run

fi

# Copy sample files.
if [ ! -f $www_base/app/config/apis.conf.php ]
then
	cp $www_base/app/config/apis.conf.sample.php $www_base/app/config/apis.conf.php
fi

if [ ! -f $www_base/app/config/db.conf.php ]
then
	cp $www_base/app/config/db.conf.sample.php $www_base/app/config/db.conf.php
fi

# Run Composer.js
if [ ! -f $www_base/vendor/autoload.php ]
then
	cd $www_base
	composer install
fi

# Shut off Cron tasks for now
sudo service cron stop
sudo service nginx stop
sudo service pvlnode stop

# Set up DB.
echo "Setting up database..."

cd $www_base/util

sudo -u vagrant php doctrine.php orm:schema-tool:drop --force

sudo -u vagrant php doctrine.php orm:schema-tool:create
sudo -u vagrant php cli.php cache:clear

sudo -u vagrant php vagrant_import.php

sudo service pvlnode start

echo "Importing external music databases (takes a minute)..."
sudo -u vagrant php cli.php sync:long

echo "Running regular tasks..."
sudo -u vagrant php cli.php sync:short
sudo -u vagrant php cli.php sync:medium

sudo -u vagrant php cli.php sync:nowplaying

# Add cron job
echo "Installing cron job..."
crontab -u vagrant $www_base/util/vagrant_cron

sudo service cron start
sudo service nginx start

echo "One-time setup complete!"
echo "Server now live at http://dev.pvlive.me"