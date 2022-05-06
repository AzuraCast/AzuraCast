SHELL=/bin/bash
.PHONY: *

list:
	@LC_ALL=C $(MAKE) -pRrq -f $(lastword $(MAKEFILE_LIST)) : 2>/dev/null | awk -v RS= -F: '/^# File/,/^# Finished Make data base/ {if ($$1 !~ "^[#.]") {print $$1}}' | sort | egrep -v -e '^[^[:alnum:]]' -e '^$@$$'

install: # Developer mode installation
	bash ./docker.sh install-dev

install-cloud-ide:
	cp docker-compose.sample.yml docker-compose.yml
	cp docker-compose.cloudide.yml docker-compose.override.yml

	cp dev.env .env
	cp azuracast.dev.env azuracast.env

	docker-compose pull
	docker-compose build
	docker-compose run --rm web azuracast_install "$@"

up:
	docker-compose up -d

down:
	docker-compose down

restart: down up

build: # Rebuild all containers and restart
	docker-compose build
	$(MAKE) restart

update: # Update everything (i.e. after a branch update)
	docker-compose build
	$(MAKE) down
	docker-compose run --rm web gosu azuracast composer install
	docker-compose run --rm web azuracast_cli azuracast:setup:initialize
	$(MAKE) frontend-build
	$(MAKE) up

test:
	docker-compose exec --user=azuracast web composer run cleanup-and-test

bash:
	docker-compose exec --user=azuracast web bash

frontend-bash:
	docker-compose -p azuracast_frontend -f docker-compose.frontend.yml build
	docker-compose -p azuracast_frontend -f docker-compose.frontend.yml run -e NODE_ENV=development --rm frontend

frontend-build:
	docker-compose -p azuracast_frontend -f docker-compose.frontend.yml build
	docker-compose -p azuracast_frontend -f docker-compose.frontend.yml run -e NODE_ENV=development --rm frontend npm run build

generate-locales:
	docker-compose -p azuracast_frontend -f docker-compose.frontend.yml build
	docker-compose -p azuracast_frontend -f docker-compose.frontend.yml run -e NODE_ENV=development --rm frontend npm run generate-locales
	docker-compose exec --user=azuracast web azuracast_cli locale:generate

import-locales:
	docker-compose -p azuracast_frontend -f docker-compose.frontend.yml build
	docker-compose -p azuracast_frontend -f docker-compose.frontend.yml run -e NODE_ENV=development --rm frontend npm run import-locales
	docker-compose exec --user=azuracast web azuracast_cli locale:import

