#!/usr/bin/env bash

# Add Phalcon PPA
apt-add-repository ppa:phalcon/stable

# Add MariaDB repo
apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xcbcb082a1bb943db
add-apt-repository 'deb http://nyc2.mirrors.digitalocean.com/mariadb/repo/10.0/ubuntu trusty main'

apt-get update

# Install app dependencies
apt-get -q -y install mariadb-server php5-fpm php5-cli php5-gd php5-mysqlnd php5-curl php5-phalcon
apt-get -q -y install nodejs npm

# Set up InfluxDB early (to allow time to initialize before setting up DBs.)
cd ~
wget http://influxdb.s3.amazonaws.com/influxdb_0.8.8_amd64.deb
dpkg -i influxdb_0.8.8_amd64.deb
service influxdb start

# Set Node.js bin alias
ln -s /usr/bin/nodejs /usr/bin/node

# Set MySQL root password
mysqladmin -u root password password

# Set up environment.
echo 'development' > $app_base/app/.env

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
cp /vagrant/util/vagrant_nginx /etc/nginx/nginx.conf
sed -e '/AZURABASEDIR/'$www_base'/' /etc/nginx/nginx.conf

# chown -R vagrant /var/log/nginx

unlink /etc/nginx/sites-enabled/

# Set up MySQL server.
echo "Customizing MySQL..."

cat $www_base/util/vagrant_mycnf >> /etc/mysql/my.cnf

echo "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY 'password' WITH GRANT OPTION;" | mysql -u root -ppassword
echo "CREATE DATABASE azuracast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | mysql -u root -ppassword
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
#cd $www_base
#npm install -g gulp
#npm install --no-bin-links

#cd $www_base/live
#npm install --no-bin-links

# Mark deployment as run.
touch $app_base/.deploy_run

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

if [ ! -f $www_base/app/config/cache.conf.php ]
then
	cp $www_base/app/config/cache.conf.sample.php $www_base/app/config/cache.conf.php
fi

# Run Composer.js
cd $www_base
composer install

# Shut off Cron tasks for now
service cron stop
service nginx stop

# Set up DB.
echo "Setting up database..."

cd $www_base/util

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