#!/usr/bin/env bash

export www_base="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
export app_base=$www_base/..
export util_base=$www_base/util
export tmp_base=$app_base/www_tmp

export app_env="production"

cd $util_base
chmod a+x ./util/install_radio.sh
chmod a+x ./util/install_app.sh

./util/install_radio.sh
./util/install_app.sh