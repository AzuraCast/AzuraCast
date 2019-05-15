#!/usr/bin/env bash

# This is a general-purpose function to ask Yes/No questions in Bash, either
# with or without a default answer. It keeps repeating the question until it
# gets a valid answer.
ask() {
    # https://djm.me/ask
    local prompt default reply

    while true; do

        if [[ "${2:-}" = "Y" ]]; then
            prompt="Y/n"
            default=Y
        elif [[ "${2:-}" = "N" ]]; then
            prompt="y/N"
            default=N
        else
            prompt="y/n"
            default=
        fi

        # Ask the question (not using "read -p" as it uses stderr not stdout)
        echo -n "$1 [$prompt] "

        read reply

        # Default?
        if [[ -z "$reply" ]]; then
            reply=${default}
        fi

        # Check if the reply is valid
        case "$reply" in
            Y*|y*) return 0 ;;
            N*|n*) return 1 ;;
        esac

    done
}

while [[ "$1" =~ ^- && ! "$1" == "--" ]]; do case $1 in
    --dev)
        APP_ENV="development"
        shift
        ;;
esac; shift; done
if [[ "$1" == '--' ]]; then shift; fi

if ask "Use Docker installation method? (Recommended)" Y; then
    bash docker.sh install
else
    PKG_OK=$(dpkg-query -W --showformat='${Status}\n' ansible|grep "install ok installed")
    echo "Checking for Ansible: $PKG_OK"

    if [[ "" == "$PKG_OK" ]]; then
        sudo apt-get update
        sudo apt-get install -q -y software-properties-common
        sudo add-apt-repository -y ppa:ansible/ansible

        sudo apt-get update
        sudo apt-get install -q -y python2.7 python-pip python-mysqldb ansible
    fi

    APP_ENV="${APP_ENV:-production}"

    echo "Installing AzuraCast (Environment: $APP_ENV)"
    ansible-playbook util/ansible/deploy.yml --inventory=util/ansible/hosts --extra-vars "app_env=$APP_ENV"
fi
