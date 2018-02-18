#!/usr/bin/env bash

APP_ENV="${APP_ENV:-production}"

echo "Installing AzuraCast (Environment: $APP_ENV)"
ansible-playbook util/ansible/uninstall.yml --inventory=util/ansible/hosts --extra-vars "app_env=$APP_ENV"