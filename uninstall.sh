#!/usr/bin/env bash

APP_ENV="${APP_ENV:-production}"

read -p "WARNING: This operation is destructive and will uninstall software on this server. Continue? [y/N] " -n 1 -r
echo

if [[ $REPLY =~ ^[Yy]$ ]]; then

    echo "Uninstalling AzuraCast..."
    ansible-playbook util/ansible/uninstall.yml --inventory=util/ansible/hosts --extra-vars "app_env=$APP_ENV"

    echo " "
    echo "Uninstallation complete. Some components were not removed."
    echo " "
    echo "To automatically remove unnecessary packages, run:"
    echo "  apt-get autoremove"
    echo " "
    echo "To remove MariaDB data, run:"
    echo "  rm -rfv /etc/mysql /var/lib/mysql"
    echo " "
    echo "To remove AzuraCast station data, run:"
    echo "  rm -rf /var/azuracast/stations"
    echo " "
    echo "If moving to Docker, you can remove every file in this folder except docker-compose.yml."
    echo "Thanks for using AzuraCast!"
    echo " "

fi