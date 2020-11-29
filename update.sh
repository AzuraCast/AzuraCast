#!/usr/bin/env bash

while [[ "$1" =~ ^- && ! "$1" == "--" ]]; do
    case $1 in
    --dev)
        APP_ENV="development"
        ;;

    --full)
        UPDATE_REVISION=0
        ;;
    esac
    shift
done
if [[ "$1" == '--' ]]; then shift; fi

APP_ENV="${APP_ENV:-production}"
UPDATE_REVISION="${UPDATE_REVISION:-58}"

PKG_OK=$(dpkg-query -W --showformat='${Status}\n' ansible | grep "install ok installed")
echo "Checking for Ansible: $PKG_OK"

if [[ "" == "$PKG_OK" ]]; then
    sudo apt-get update
    sudo apt-get install -q -y software-properties-common
    sudo apt-add-repository ppa:ansible/ansible
    sudo apt-get update
    sudo apt-get install -q -y ansible python-mysqldb
else
    sudo apt-get update
    sudo apt-get install -q -y ansible python-mysqldb
fi

echo "Updating AzuraCast (Environment: $APP_ENV, Update revision: $UPDATE_REVISION)"

if [[ ${APP_ENV} == "production" ]]; then
    if [[ -d ".git" ]]; then
        git reset --hard
        git pull
    else
        echo "You are running a downloaded release build. Any code updates should be applied manually."
    fi
fi

ansible-playbook util/ansible/update.yml --inventory=util/ansible/hosts --extra-vars "app_env=$APP_ENV update_revision=$UPDATE_REVISION"
