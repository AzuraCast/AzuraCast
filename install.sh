#!/usr/bin/env bash

export www_base="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
export app_base=$www_base/..
export util_base=$www_base/util
export tmp_base=$app_base/www_tmp

cd $util_base
chmod a+x ./install_radio.sh
chmod a+x ./install_app.sh

./install_radio.sh
./install_app.sh

export external_ip = `dig +short myip.opendns.com @resolver1.opendns.com`
echo "Base installation complete!"
echo "Continue setup at http://$external_ip:8080"