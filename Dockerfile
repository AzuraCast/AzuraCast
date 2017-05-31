FROM geerlingguy/docker-ubuntu1604-ansible:latest

RUN ansible-playbook /var/azuracast/www/util/ansible/deploy.yml \
    --extra-vars "update_revision=1"

EXPOSE 80
EXPOSE 443
EXPOSE 8000-8999

CMD ["/usr/bin/supervisord"]