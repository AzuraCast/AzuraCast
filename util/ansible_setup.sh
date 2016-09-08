#!/usr/bin/env bash

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