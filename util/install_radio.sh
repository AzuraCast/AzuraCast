#!/usr/bin/env bash

function sedeasy {
  sed -i "s/$(echo $1 | sed -e 's/\([[\/.*]\|\]\)/\\&/g')/$(echo $2 | sed -e 's/[\/&]/\\&/g')/g" $3
}

apt-get update

apt-get -q -y install pwgen icecast2 liquidsoap

# Generate new passwords for Icecast

export icecast_pw_source=`pwgen 8`
export icecast_pw_relay=`pwgen 8`
export icecast_pw_admin=`pwgen 8`

sedeasy "<source-password>hackme</source-password>" "<source-password>'$icecast_pw_source'</source-password>" /etc/icecast2/icecast.xml
sedeasy "<relay-password>hackme</relay-password>" "<relay-password>'$icecast_pw_relay'</relay-password>" /etc/icecast2/icecast.xml
sedeasy "<admin-password>hackme</admin-password>" "<admin-password>'$icecast_pw_admin'</admin-password>" /etc/icecast2/icecast.xml