#!/bin/bash
set -e
set -x

mkdir -p /tmp/autocue
mkdir -p /var/azuracast/autocue

cd /tmp/autocue
git clone https://github.com/Moonbase59/autocue .
git checkout integrate-with-liquidsoap

mv ./cue_file /usr/local/bin/cue_file
chmod a+x /usr/local/bin/cue_file

mv ./autocue.cue_file.liq /var/azuracast/autocue/autocue.liq
chown -R azuracast:azuracast /var/azuracast/autocue

cd /var/azuracast/autocue
rm -rf /tmp/autocue
