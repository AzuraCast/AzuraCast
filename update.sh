#!/usr/bin/env bash

function phpuser {
  sudo -u azuracast php $@
}

export www_base="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
export app_base=`realpath $www_base/..`
export util_base=$www_base/util
export tmp_base=$app_base/www_tmp

# Stop system tasks
service cron stop
service nginx stop
service php5-fpm stop

# Pull down update
git reset --hard
git pull

chmod a+x ./update.sh

# Clear cache
rm -rf $tmp_base/cache/*

cd $util_base
phpuser cli.php cache:clear
phpuser doctrine.php orm:schema-tool:update --force

# Restart services
service cron start
service php5-fpm start
service nginx start