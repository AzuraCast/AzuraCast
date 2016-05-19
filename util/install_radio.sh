#!/usr/bin/env bash

function sedeasy {
  sed -i "s/$(echo $1 | sed -e 's/\([[\/.*]\|\]\)/\\&/g')/$(echo $2 | sed -e 's/[\/&]/\\&/g')/g" $3
}

# Suppress some prompts
export DEBIAN_FRONTEND=noninteractive

# Use External PPA for more up-to-date version of IceCast
wget -qO - http://icecast.org/multimedia-obs.key | sudo apt-key add -
sudo sh -c "echo deb http://download.opensuse.org/repositories/multimedia:/xiph/xUbuntu_14.04/ ./ >>/etc/apt/sources.list.d/icecast.list"

apt-get update
apt-get -q -y install icecast2 liquidsoap