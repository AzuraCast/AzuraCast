#!/usr/bin/env bash

while test $# -gt 0; do
    case "$1" in
        --dev)
            APP_ENV="development"
            shift
            ;;

        --full)
           UPDATE_REVISION=0
           shift
           ;;

        *)
            break
            ;;
    esac
done

PKG_OK=$(dpkg-query -W --showformat='${Status}\n' ansible|grep "install ok installed")
echo Checking for Ansible: $PKG_OK

if [ "" == "$PKG_OK" ]; then
    sudo apt-get update
    sudo apt-get install -q -y software-properties-common
    sudo apt-add-repository ppa:ansible/ansible
    sudo apt-get update
    sudo apt-get install -q -y ansible python-mysqldb
else
    sudo apt-get update
    sudo apt-get install -q -y ansible python-mysqldb
fi

APP_ENV="${APP_ENV:-production}"
UPDATE_REVISION="${UPDATE_REVISION:-26}"

echo "Updating AzuraCast (Environment: $APP_ENV, Update revision: $UPDATE_REVISION)"

if [ $APP_ENV = "production" ]; then
    git reset --hard && git pull
fi

ansible-playbook util/ansible/update.yml --inventory=util/ansible/hosts --extra-vars "app_env=$APP_ENV update_revision=$UPDATE_REVISION"
