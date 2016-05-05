#!/usr/bin/env bash

function sedeasy {
  sed -i "s/$(echo $1 | sed -e 's/\([[\/.*]\|\]\)/\\&/g')/$(echo $2 | sed -e 's/[\/&]/\\&/g')/g" $3
}

# Copy sample files.
if [ ! -f $www_base/app/config/apis.conf.php ]; then
	cp $www_base/app/config/apis.conf.sample.php $www_base/app/config/apis.conf.php
fi

if [ ! -f $www_base/app/config/db.conf.php ]; then
	cp $www_base/app/config/db.conf.sample.php $www_base/app/config/db.conf.php
fi

if [ ! -f $www_base/app/config/influx.conf.php ]; then
	cp $www_base/app/config/influx.conf.sample.php $www_base/app/config/influx.conf.php
fi

if [ ! -f $www_base/app/config/cache.conf.php ]; then
	cp $www_base/app/config/cache.conf.sample.php $www_base/app/config/cache.conf.php
fi

# Add Phalcon PPA
apt-add-repository ppa:phalcon/stable

# Add MariaDB repo
apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xcbcb082a1bb943db
add-apt-repository 'deb http://nyc2.mirrors.digitalocean.com/mariadb/repo/10.0/ubuntu trusty main'

apt-get update

# Install app dependencies
apt-get -q -y install nginx mariadb-server php5-fpm php5-cli php5-gd php5-mysqlnd php5-curl php5-phalcon
apt-get -q -y install nodejs npm

# Set up InfluxDB early (to allow time to initialize before setting up DBs.)
cd ~
wget -q http://influxdb.s3.amazonaws.com/influxdb_0.8.8_amd64.deb
dpkg -i influxdb_0.8.8_amd64.deb
service influxdb start

# Set Node.js bin alias
ln -s /usr/bin/nodejs /usr/bin/node

# Set up environment.
echo $app_env > $app_base/app/.env

echo "Creating temporary folders..."
mkdir -p $tmp_base
mkdir -p $tmp_base/cache
mkdir -p $tmp_base/sessions
mkdir -p $tmp_base/proxies

# Create log files.
chown -R www-data:www-data $app_base/
chmod -R 777 $tmp_base

# Nginx setup.
echo "Customizing nginx..."

service nginx stop

mv /etc/nginx/nginx.conf /etc/nginx/nginx.conf.bak
cp $util_base/vagrant_nginx /etc/nginx/nginx.conf

sedeasy "AZURABASEDIR" $app_base /etc/nginx/nginx.conf

unlink /etc/nginx/sites-enabled/

# Set up MySQL server.
echo "Customizing MySQL..."

# Set MySQL root password
if [ $app_env = "development" ]; then
    export mysql_pw=password
else
    export mysql_pw=$(pwgen 8 -sn 1)
fi

mysqladmin -u root password $mysql_pw

sedeasy "'password'," "'$mysql_pw'," $www_base/app/config/db.conf.php

cat $www_base/util/vagrant_mycnf >> /etc/mysql/my.cnf

echo "CREATE DATABASE azuracast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | mysql -u root -p$mysql_pw
service mysql restart

# Preconfigure databases
cd $www_base/util
curl -X POST "http://localhost:8086/cluster/database_configs/pvlive_stations?u=root&p=root" --data-binary @influx_pvlive_stations.json
curl -X POST "http://localhost:8086/cluster/database_configs/pvlive_analytics?u=root&p=root" --data-binary @influx_pvlive_analytics.json

# Enable PHP flags.
sed -e '/^[^;]*short_open_tag/s/=.*$/= On/' -i /etc/php5/fpm/php.ini
sed -e '/^[^;]*short_open_tag/s/=.*$/= On/' -i /etc/php5/cli/php.ini

mv /etc/php5/fpm/pool.d/www.conf /etc/php5/fpm/www.conf.bak
cp /vagrant/util/vagrant_phpfpm.conf /etc/php5/fpm/pool.d/www.conf

service php5-fpm restart

# Install composer.
echo "Installing Composer..."
cd ~
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install node.js and dependencies
if [ $app_env = "development" ]; then
    mkdir -p /var/azuracast/build
    chown -R vagrant:www-data /var/azuracast/build

    ln -s $www_base/web/static/gruntfile.js /var/azuracast/build/gruntfile.js
    ln -s $www_base/web/static/package.json /var/azuracast/build/package.json

    cd /var/azuracast/build
    npm install --loglevel warn
    npm install -g bower --loglevel warn
    npm install -g grunt --loglevel warn
fi

# Mark deployment as run.
touch $app_base/.deploy_run

# Run Composer.js
cd $www_base
composer install

# Shut off Cron tasks for now
service cron stop
service nginx stop

# Set up DB.
echo "Setting up database..."

cd $util_base

php doctrine.php orm:schema-tool:drop --force

php doctrine.php orm:schema-tool:create
php cli.php cache:clear

#echo "Importing external music databases (takes a minute)..."
#sudo -u vagrant php cli.php sync:long

#echo "Running regular tasks..."
#sudo -u vagrant php cli.php sync:short
#sudo -u vagrant php cli.php sync:medium
#sudo -u vagrant php cli.php sync:nowplaying

# Add cron job
echo "Installing cron job..."
crontab -u vagrant $www_base/util/vagrant_cron

service cron start
service nginx start