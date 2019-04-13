#!/usr/bin/env bash

release_update=0

while [[ "$1" =~ ^- && ! "$1" == "--" ]]; do case $1 in
    --dev)
        APP_ENV="development"
        ;;

    -r | --release )
        release_update=1
        ;;

    --full)
        UPDATE_REVISION=0
        ;;
esac; shift; done
if [[ "$1" == '--' ]]; then shift; fi

PKG_OK=$(dpkg-query -W --showformat='${Status}\n' ansible|grep "install ok installed")
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

APP_ENV="${APP_ENV:-production}"
UPDATE_REVISION="${UPDATE_REVISION:-39}"

echo "Updating AzuraCast (Environment: $APP_ENV, Update revision: $UPDATE_REVISION)"

if [[ ${APP_ENV} = "production" ]]; then
    if [[ -d ".git" ]]; then
        if [[ $release_update = 1 ]]; then
            current_hash=$(git rev-parse HEAD)
            current_tag=$(git describe --abbrev=0 --tags)

            git fetch --tags
            latest_tag=$(git describe --abbrev=0 --tags)

            git reset --hard

            if [[ $current_tag = $latest_tag ]]; then
                echo "You are already on the latest version (${current_tag})!"
            else
                echo "Updating codebase from ${current_tag} to ${latest_tag}..."

                git pull
                git reset --hard $latest_tag
            fi
        else
            echo "Updating to the latest rolling-release version..."
            echo "Tip: use the '--release' flag to update to tagged releases only."

            git reset --hard
            git pull
        fi
    else
        echo "You are running a release build. Any code updates should be applied manually."
    fi
fi

ansible-playbook util/ansible/update.yml --inventory=util/ansible/hosts --extra-vars "app_env=$APP_ENV update_revision=$UPDATE_REVISION"
