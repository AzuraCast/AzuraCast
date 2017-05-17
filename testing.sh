#!/usr/bin/env bash

PKG_OK=$(dpkg-query -W --showformat='${Status}\n' ansible|grep "install ok installed")
echo Checking for Ansible: $PKG_OK

if [ "" == "$PKG_OK" ]; then
    sudo apt-get update
    sudo apt-get install -q -y software-properties-common
    sudo add-apt-repository -y ppa:ansible/ansible

    sudo add-apt-repository -y ppa:fkrull/deadsnakes-python2.7

    sudo apt-get update
    sudo apt-get install -q -y python2.7 python-pip python-mysqldb ansible
fi

echo "Installing AzuraCast (Functional Testing Mode)"
ansible-playbook util/ansible/deploy.yml --inventory=util/ansible/hosts --extra-vars "app_env=development testing_mode=true"