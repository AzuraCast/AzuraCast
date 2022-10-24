#!/usr/bin/env bash

while [[ "$1" =~ ^- && ! "$1" == "--" ]]; do
  case $1 in
  --dev)
    APP_ENV="development"
    shift
    ;;
  esac
  shift
done
if [[ "$1" == '--' ]]; then shift; fi

. /etc/lsb-release

if [[ $DISTRIB_ID != "Ubuntu" ]]; then
  echo "Ansible installation is only supported on Ubuntu distributions."
  exit 0
fi

sudo apt-get update
sudo apt-get install -q -y software-properties-common

if [[ $DISTRIB_CODENAME == "focal" || $DISTRIB_CODENAME == "jammy" ]]; then
  sudo apt-get install -q -y ansible python3-pip python3-mysqldb
else
  echo "Ansible installation is only supported on Ubuntu Focal (20.04) or Jammy (22.04)."
  exit 0
fi

APP_ENV="${APP_ENV:-production}"

echo "Installing AzuraCast (Environment: $APP_ENV)"
ansible-playbook util/ansible/deploy.yml --inventory=util/ansible/hosts --extra-vars "app_env=$APP_ENV"
