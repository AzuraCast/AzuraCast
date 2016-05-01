#!/usr/bin/env bash

# Use closest mirrors available
sudo mv /etc/apt/sources.list /etc/apt/sources.list.bak
echo "deb mirror://mirrors.ubuntu.com/mirrors.txt trusty main restricted universe multiverse" | sudo tee --append /etc/apt/sources.list
echo "deb mirror://mirrors.ubuntu.com/mirrors.txt trusty-updates main restricted universe multiverse" | sudo tee --append /etc/apt/sources.list
echo "deb mirror://mirrors.ubuntu.com/mirrors.txt trusty-backports main restricted universe multiverse" | sudo tee --append /etc/apt/sources.list
echo "deb mirror://mirrors.ubuntu.com/mirrors.txt trusty-security main restricted universe multiverse" | sudo tee --append /etc/apt/sources.list

# Node JS
sudo add-apt-repository -y ppa:chris-lea/node.js
sudo apt-get update
sudo apt-get install -y nodejs fontforge ttfautohint

# ttfautohint - there is trouble with 0.9.7
# *** Error in `ttfautohint': malloc(): memory corruption: 0x000000000153de20 ***
# so we need to install new one from sources instead of packages
sudo apt-get install -y autoconf automake bison flex git libtool perl build-essential libharfbuzz-dev pkg-config libfreetype6-dev libfreetype6

git clone git://repo.or.cz/ttfautohint.git
cd ttfautohint
git checkout v1.1
./bootstrap
./configure --with-qt=no --with-doc=no
make
sudo make install
cd ..
rm -rfv ttfautohint/
sudo ln -s /usr/local/bin/ttfautohint /usr/bin/

sudo npm install -g grunt
sudo npm install -g grunt-cli

cd
cd ../../vagrant/

sudo npm install
grunt font-dev