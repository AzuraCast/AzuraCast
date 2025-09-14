SHELL=/bin/bash
.PHONY: *

list:
	@LC_ALL=C $(MAKE) -pRrq -f $(lastword $(MAKEFILE_LIST)) : 2>/dev/null | awk -v RS= -F: '/^# File/,/^# Finished Make data base/ {if ($$1 !~ "^[#.]") {print $$1}}' | sort | egrep -v -e '^[^[:alnum:]]' -e '^$@$$'

install: # Developer mode installation
	bash ./docker.sh install-dev

up:
	docker compose up -d

down:
	docker compose down

restart: down up

build: # Rebuild all containers and restart
	docker compose build
	$(MAKE) restart

post-update:
	$(MAKE) down
	docker compose run --rm web azuracast_dev_install --update
	$(MAKE) up

update: # Update everything (i.e. after a branch update)
	docker compose build
	$(MAKE) post-update

build-depot: # Rebuild all containers with Depot and restart
	depot bake -f docker-compose.yml -f docker-compose.override.yml --load
	$(MAKE) restart

update-depot: # Update everything using Depot
	depot bake -f docker-compose.yml -f docker-compose.override.yml --load
	$(MAKE) post-update

test:
	docker compose exec --user=azuracast web composer run cleanup-and-test

bash:
	docker compose exec --user=azuracast web bash

bash-root:
	docker compose exec web bash

generate-locales:
	docker compose exec --user=azuracast web azuracast_cli locale:generate

import-locales:
	docker compose exec --user=azuracast web azuracast_cli locale:import

