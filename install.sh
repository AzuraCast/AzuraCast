#!/usr/bin/env bash

export app_base = "$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
export util_base = $app_base/util
export www_base = $app_base
export tmp_base= $app_base/tmp

cd $util_base

chmod a+x ./install_app.sh
chmod a+x ./install_radio.sh

./install_app.sh
./install_radio.sh

export external_ip = `dig +short myip.opendns.com @resolver1.opendns.com`

echo "Base installation complete!"
echo "Continue setup at http://$external_ip:8080"