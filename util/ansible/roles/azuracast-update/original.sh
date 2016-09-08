#!/usr/bin/env bash

# Pull down update
git reset --hard
git pull

chmod a+x ./update.sh

cd $util_base
phpuser cli.php cache:clear
phpuser doctrine.php orm:schema-tool:update --force --complete