#!/usr/bin/env bash

# Add Vagrant user to the sudoers group
echo 'vagrant ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers

# Set up swap partition
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo "/swapfile   none    swap    sw    0   0" >> /etc/fstab

sudo apt-get update
sudo apt-get install -q -y software-properties-common
sudo apt-add-repository ppa:ansible/ansible
sudo apt-get update
sudo apt-get install -q -y ansible python-mysqldb

cat > /home/vagrant/.ansible.cfg <<EOF
[defaults]
remote_tmp = /vagrant/ansible/tmp
log_path = /vagrant/ansible/ansible.log
EOF