#!/usr/bin/env bash

APP_ENV="${APP_ENV:-production}"

echo "Installing AzuraCast (Environment: $APP_ENV)"
ansible-playbook util/ansible/uninstall.yml --inventory=util/ansible/hosts --extra-vars "app_env=$APP_ENV"

echo " "
echo "Uninstallation complete. Some components were not removed."
echo " "
echo "To automatically remove unnecessary packages, run:"
echo "  apt-get autoremove"
echo " "
echo "To remove AzuraCast station data, run:"
echo "  rm -rf /var/azuracast/stations"
echo " "