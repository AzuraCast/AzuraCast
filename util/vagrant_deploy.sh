#!/bin/bash

export DEBIAN_FRONTEND=noninteractive
export app_base=/var/www
export tmp_base=$app_base/www_tmp
export www_base=$app_base/vagrant

if [ ! -f $app_base/.deploy_run ]
then

    # Add Vagrant user to the sudoers group
    echo 'vagrant ALL=(ALL) ALL' >> /etc/sudoers

    # Set up swap partition
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo "/swapfile   none    swap    sw    0   0" >> /etc/fstab

    # Set up server
    apt-get -q -y install software-properties-common python-software-properties

    # Add Phalcon PPA
    apt-add-repository ppa:phalcon/stable

    # Add MariaDB repo
    apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xcbcb082a1bb943db
    add-apt-repository 'deb http://nyc2.mirrors.digitalocean.com/mariadb/repo/10.0/ubuntu trusty main'

    apt-get update

    apt-get -q -y install vim git curl nginx mariadb-server
    apt-get -q -y install php5-fpm php5-cli php5-gd php5-mysqlnd php5-curl php5-phalcon php5-redis php5-memcached
    apt-get -q -y install nodejs npm
    apt-get autoremove

    # Set up InfluxDB early (to allow time to initialize before setting up DBs.)
    cd ~
    wget http://s3.amazonaws.com/influxdb/influxdb_latest_amd64.deb
    dpkg -i influxdb_latest_amd64.deb
    service influxdb start

    # Set Node.js bin alias
    ln -s /usr/bin/nodejs /usr/bin/node

    # Set MySQL root password
    mysqladmin -u root password password

    # Trigger mlocate reindex.
    updatedb

    # Set up environment.
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

    unlink /etc/nginx/sites-enabled/

    # Set up MySQL server.
    echo "Customizing MySQL..."

    cat $www_base/util/vagrant_mycnf >> /etc/mysql/my.cnf

    echo "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY 'password' WITH GRANT OPTION;" | mysql -u root -ppassword
    echo "CREATE DATABASE pvl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | mysql -u root -ppassword
    service mysql restart

    # Preconfigure databases
    cd $www_base/util
    curl -X POST "http://localhost:8086/cluster/database_configs/pvlive_stations?u=root&p=root" --data-binary @influx_pvlive_stations.json
    curl -X POST "http://localhost:8086/cluster/database_configs/pvlive_analytics?u=root&p=root" --data-binary @influx_pvlive_analytics.json

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
    cd $www_base
    npm install -g gulp
    npm install --no-bin-links

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

if [ ! -f $www_base/app/config/influx.conf.php ]
then
	cp $www_base/app/config/influx.conf.sample.php $www_base/app/config/influx.conf.php
fi

if [ ! -f $www_base/app/config/memcached.conf.php ]
then
	cp $www_base/app/config/memcached.conf.sample.php $www_base/app/config/memcached.conf.php
fi

# Run Composer.js
cd $www_base
composer install

# Shut off Cron tasks for now
service cron stop
service nginx stop
service pvlnode stop

# Set up DB.
echo "Setting up database..."

cd $www_base/util

sudo -u vagrant php doctrine.php orm:schema-tool:drop --force

sudo -u vagrant php doctrine.php orm:schema-tool:create
sudo -u vagrant php cli.php cache:clear

sudo -u vagrant php vagrant_import.php

service pvlnode start

echo "Importing external music databases (takes a minute)..."
sudo -u vagrant php cli.php sync:long

echo "Running regular tasks..."
sudo -u vagrant php cli.php sync:short
sudo -u vagrant php cli.php sync:medium

sudo -u vagrant php cli.php sync:nowplaying

# Add cron job
echo "Installing cron job..."
crontab -u vagrant $www_base/util/vagrant_cron

service cron start
service nginx start

echo "One-time setup complete!"
echo "Server now live at http://dev.pvlive.me"