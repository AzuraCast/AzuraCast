#!/usr/bin/env bash

sudo apt-get update
sudo apt-get install -q -y software-properties-common
sudo apt-add-repository ppa:ansible/ansible
sudo apt-get update
sudo apt-get install -q -y ansible python-mysqldb

ansible-playbook util/ansible/deploy.yml --inventory=util/ansible/hosts --extra-vars "app_env=production"