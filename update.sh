#!/usr/bin/env bash

function phpuser {
  sudo -u azuracast php $@
}

export www_base="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
export app_base=`realpath $www_base/..`
export util_base=$www_base/util
export tmp_base=$app_base/www_tmp

# Stop system tasks
sudo service nginx stop
sudo service cron stop

# Pull down update
git reset --hard
git pull

# Update Phalcon
sudo apt-get update
sudo apt-get -q -y install php5-phalcon

# Relink fallback mp3 file to IceCast web directory.
sudo rm -f /usr/share/icecast2/web/error.mp3
sudo ln -s $www_base/resources/error.mp3 /usr/share/icecast2/web/

# Reset cron tasks for AzuraCast account
sudo crontab -u azuracast $www_base/util/vagrant_cron

chmod a+x ./update.sh

# Update Composer dependencies
composer install

# Clear cache
rm -rf $tmp_base/cache/*

cd $util_base
phpuser cli.php cache:clear
phpuser doctrine.php orm:schema-tool:update --force --complete

# Restart services
service cron start
service nginx start