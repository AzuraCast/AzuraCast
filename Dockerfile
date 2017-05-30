FROM geerlingguy/docker-ubuntu1604-ansible:latest

RUN ansible-playbook

APP_ENV="${APP_ENV:-production}"

echo "Installing AzuraCast (Environment: $APP_ENV)"
ansible-playbook util/ansible/deploy.yml --inventory=util/ansible/hosts --extra-vars "app_env=$APP_ENV"

CMD ["/usr/bin/supervisord"]