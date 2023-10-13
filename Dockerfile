#
# Golang dependencies build step
#
FROM golang:1.20-bullseye AS go-dependencies

RUN apt-get update \
    && apt-get install -y --no-install-recommends openssl git

RUN go install github.com/jwilder/dockerize@v0.6.1

RUN go install github.com/aptible/supercronic@v0.2.26

RUN go install github.com/centrifugal/centrifugo/v5@v5.1.0

#
# MariaDB dependencies build step
#
FROM mariadb:10.9-jammy AS mariadb

#
# Final build image
#
FROM ubuntu:jammy AS pre-final

ENV TZ="UTC"

# Add Go dependencies
COPY --from=go-dependencies /go/bin/dockerize /usr/local/bin
COPY --from=go-dependencies /go/bin/supercronic /usr/local/bin/supercronic
COPY --from=go-dependencies /go/bin/centrifugo /usr/local/bin/centrifugo

# Add MariaDB dependencies
COPY --from=mariadb /usr/local/bin/healthcheck.sh /usr/local/bin/db_healthcheck.sh
COPY --from=mariadb /usr/local/bin/docker-entrypoint.sh /usr/local/bin/db_entrypoint.sh

# Run base build process
COPY ./util/docker/common /bd_build/

RUN bash /bd_build/prepare.sh \
    && bash /bd_build/add_user.sh

# Build each set of dependencies in their own step for cacheability.
COPY ./util/docker/supervisor /bd_build/supervisor/
RUN bash /bd_build/supervisor/setup.sh

COPY ./util/docker/stations /bd_build/stations/
RUN bash /bd_build/stations/setup.sh

COPY ./util/docker/web /bd_build/web/
RUN bash /bd_build/web/setup.sh

COPY ./util/docker/mariadb /bd_build/mariadb/
RUN bash /bd_build/mariadb/setup.sh

COPY ./util/docker/redis /bd_build/redis/
RUN bash /bd_build/redis/setup.sh

COPY ./util/docker/docs /bd_build/docs/
RUN bash /bd_build/docs/setup.sh

RUN bash /bd_build/cleanup.sh \
    && rm -rf /bd_build

USER azuracast

RUN touch /var/azuracast/.docker

USER root

VOLUME ["/var/azuracast/stations", "/var/azuracast/uploads", "/var/azuracast/backups", "/var/azuracast/sftpgo/persist", "/var/azuracast/servers/shoutcast2", "/var/azuracast/meilisearch/persist"]

#
# Development Build
#
FROM pre-final AS development

# Dev build step
COPY ./util/docker/common /bd_build/
COPY ./util/docker/dev /bd_build/dev

RUN bash /bd_build/dev/setup.sh \
    && bash /bd_build/cleanup.sh \
    && rm -rf /bd_build

USER azuracast

WORKDIR /var/azuracast/www

COPY --chown=azuracast:azuracast . .

RUN composer install --no-ansi --no-interaction

WORKDIR /var/azuracast/www/frontend

RUN npm ci --include=dev

WORKDIR /var/azuracast/www

USER root

EXPOSE 80 443 2022
EXPOSE 8000-8999

# Sensible default environment variables.
ENV TZ="UTC" \
    LANG="en_US.UTF-8" \
    PATH="${PATH}:/var/azuracast/servers/shoutcast2" \
    DOCKER_IS_STANDALONE="true" \
    APPLICATION_ENV="development" \
    MYSQL_HOST="localhost" \
    MYSQL_PORT=3306 \
    MYSQL_USER="azuracast" \
    MYSQL_PASSWORD="azur4c457" \
    MYSQL_DATABASE="azuracast" \
    ENABLE_REDIS="true" \
    REDIS_HOST="localhost" \
    REDIS_PORT=6379 \
    REDIS_DB=1 \
    NGINX_RADIO_PORTS="default" \
    NGINX_WEBDJ_PORTS="default" \
    COMPOSER_PLUGIN_MODE="false" \
    ADDITIONAL_MEDIA_SYNC_WORKER_COUNT=0 \
    PROFILING_EXTENSION_ENABLED=1 \
    PROFILING_EXTENSION_ALWAYS_ON=0 \
    PROFILING_EXTENSION_HTTP_KEY=dev \
    PROFILING_EXTENSION_HTTP_IP_WHITELIST=* \
    ENABLE_WEB_UPDATER="false"

# Entrypoint and default command
ENTRYPOINT ["tini", "--", "/usr/local/bin/my_init"]
CMD ["--no-main-command"]

#
# Final build (Just environment vars and squishing the FS)
#
FROM ubuntu:jammy AS final

COPY --from=pre-final / /

USER azuracast

WORKDIR /var/azuracast/www

COPY --chown=azuracast:azuracast ./composer.json ./composer.lock ./
RUN composer install \
    --no-dev \
    --no-ansi \
    --no-autoloader \
    --no-interaction

COPY --chown=azuracast:azuracast . .

RUN composer dump-autoload --optimize --classmap-authoritative

USER root

EXPOSE 80 443 2022
EXPOSE 8000-8999

# Sensible default environment variables.
ENV TZ="UTC" \
    LANG="en_US.UTF-8" \
    PATH="${PATH}:/var/azuracast/servers/shoutcast2" \
    DOCKER_IS_STANDALONE="true" \
    APPLICATION_ENV="production" \
    MYSQL_HOST="localhost" \
    MYSQL_PORT=3306 \
    MYSQL_USER="azuracast" \
    MYSQL_PASSWORD="azur4c457" \
    MYSQL_DATABASE="azuracast" \
    ENABLE_REDIS="true" \
    REDIS_HOST="localhost" \
    REDIS_PORT=6379 \
    REDIS_DB=1 \
    NGINX_RADIO_PORTS="default" \
    NGINX_WEBDJ_PORTS="default" \
    COMPOSER_PLUGIN_MODE="false" \
    ADDITIONAL_MEDIA_SYNC_WORKER_COUNT=0 \
    PROFILING_EXTENSION_ENABLED=0 \
    PROFILING_EXTENSION_ALWAYS_ON=0 \
    PROFILING_EXTENSION_HTTP_KEY=dev \
    PROFILING_EXTENSION_HTTP_IP_WHITELIST=* \
    ENABLE_WEB_UPDATER="true"

# Entrypoint and default command
ENTRYPOINT ["tini", "--", "/usr/local/bin/my_init"]
CMD ["--no-main-command"]
