#!/usr/bin/env bash

echo "nameserver 8.8.8.8" | sudo tee /etc/resolv.conf > /dev/null
echo "nameserver 8.8.8.8" | sudo tee /etc/resolvconf/resolv.conf.d/base > /dev/null

# Add Vagrant user to the sudoers group
echo 'ubuntu ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers

# Set up swap partition
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo "/swapfile   none    swap    sw    0   0" >> /etc/fstab

sudo apt-get update
sudo apt-get install -q -y software-properties-common

sudo add-apt-repository -y ppa:ansible/ansible
sudo apt-get update

sudo apt-get install -q -y python2.7 python-pip python-mysqldb ansible

cat > $HOME/.ansible.cfg <<EOF
[defaults]
remote_tmp = /var/azuracast/www/ansible/tmp
log_path = /var/azuracast/www/ansible/ansible.log
EOF
