.PHONY: *

list:
	@LC_ALL=C $(MAKE) -pRrq -f $(lastword $(MAKEFILE_LIST)) : 2>/dev/null | awk -v RS= -F: '/^# File/,/^# Finished Make data base/ {if ($$1 !~ "^[#.]") {print $$1}}' | sort | egrep -v -e '^[^[:alnum:]]' -e '^$@$$'

install-cloud-ide:
	cp docker-compose.cloudide.yml docker-compose.yml
	cp dev.env .env
	cp azuracast.dev.env azuracast.env

	docker-compose build
	docker-compose run --rm --user="azuracast" web azuracast_install "$@"

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
	docker-compose run --rm --user=azuracast web composer install
	docker-compose run --rm --user=azuracast web azuracast_cli azuracast:setup:initialize
	$(MAKE) frontend-build
	$(MAKE) up

test:
	docker-compose exec --user=azuracast web composer run cleanup-and-test

bash:
	docker-compose exec --user=azuracast web bash

frontend-bash:
	docker-compose -f frontend/docker-compose.yml build
	docker-compose --env-file=.env -f frontend/docker-compose.yml run -e NODE_ENV=development --rm frontend

frontend-build:
	docker-compose -f frontend/docker-compose.yml build
	docker-compose --env-file=.env -f frontend/docker-compose.yml run -e NODE_ENV=development --rm frontend npm run dev-build

