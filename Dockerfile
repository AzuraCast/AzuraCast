# syntax=docker/dockerfile:1

#
# Golang dependencies build step
#
FROM golang:1.23-bookworm AS go-dependencies

RUN apt-get update \
    && apt-get install -y --no-install-recommends openssl git

RUN go install github.com/jwilder/dockerize@v0.9.2

RUN go install github.com/aptible/supercronic@v0.2.33

RUN go install github.com/centrifugal/centrifugo/v5@v5.4.9

RUN strip /go/bin/*

#
# MariaDB dependencies build step
#
FROM mariadb:lts-noble AS mariadb

#
# Built-in docs build step
#
FROM ghcr.io/azuracast/azuracast.com:builtin AS docs

#
# Icecast-KH with AzuraCast customizations build step
#
FROM ghcr.io/azuracast/icecast-kh-ac:2024-02-13 AS icecast

#
# PHP Extension Installer build step
#
FROM mlocati/php-extension-installer AS php-extension-installer

# ---

FROM scratch AS dependencies

# Add PHP extension installer tool
COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Add Go dependencies
COPY --from=go-dependencies /go/bin/dockerize /usr/local/bin
COPY --from=go-dependencies /go/bin/supercronic /usr/local/bin/supercronic
COPY --from=go-dependencies /go/bin/centrifugo /usr/local/bin/centrifugo

# Add MariaDB dependencies
COPY --from=mariadb /usr/local/bin/healthcheck.sh /usr/local/bin/db_healthcheck.sh
COPY --from=mariadb /usr/local/bin/docker-entrypoint.sh /usr/local/bin/db_entrypoint.sh

# Add Icecast
COPY --from=icecast /usr/local/bin/icecast /usr/local/bin/icecast
COPY --from=icecast /usr/local/share/icecast /usr/local/share/icecast

#
# Final build image
#
FROM php:8.3-fpm-bookworm AS pre-final

ENV TZ="UTC" \
    LANGUAGE="en_US.UTF-8" \
    LC_ALL="en_US.UTF-8" \
    LANG="en_US.UTF-8" \
    LC_TYPE="en_US.UTF-8"

COPY --link --from=dependencies / /

# Run base build process
RUN --mount=type=bind,source=./util/docker/common,target=/bd_build,rw \
    bash /bd_build/prepare.sh && \
    bash /bd_build/add_user.sh && \
    bash /bd_build/cleanup.sh

# Build each set of dependencies in their own step for cacheability.
RUN --mount=type=bind,source=./util/docker/common,target=/bd_build,rw \
    --mount=type=bind,source=./util/docker/supervisor,target=/bd_build/supervisor,rw \
    bash /bd_build/supervisor/setup.sh && \
    bash /bd_build/cleanup.sh

RUN --mount=type=bind,source=./util/docker/common,target=/bd_build,rw \
    --mount=type=bind,source=./util/docker/stations,target=/bd_build/stations,rw \
    bash /bd_build/stations/setup.sh && \
    bash /bd_build/cleanup.sh

RUN --mount=type=bind,source=./util/docker/common,target=/bd_build,rw \
    --mount=type=bind,source=./util/docker/web,target=/bd_build/web,rw \
    bash /bd_build/web/setup.sh && \
    bash /bd_build/cleanup.sh

RUN --mount=type=bind,source=./util/docker/common,target=/bd_build,rw \
    --mount=type=bind,source=./util/docker/mariadb,target=/bd_build/mariadb,rw \
    bash /bd_build/mariadb/setup.sh && \
    bash /bd_build/cleanup.sh

RUN --mount=type=bind,source=./util/docker/common,target=/bd_build,rw \
    --mount=type=bind,source=./util/docker/redis,target=/bd_build/redis,rw \
    bash /bd_build/redis/setup.sh && \
    bash /bd_build/cleanup.sh

RUN --mount=type=bind,source=./util/docker/common,target=/bd_build,rw \
    bash /bd_build/chown_dirs.sh

# Add built-in docs
COPY --from=docs --chown=azuracast:azuracast /dist /var/azuracast/docs

USER azuracast

RUN touch /var/azuracast/.docker

USER root

VOLUME "/var/azuracast/stations"
VOLUME "/var/azuracast/backups"
VOLUME "/var/lib/mysql"
VOLUME "/var/azuracast/storage/acme"
VOLUME "/var/azuracast/storage/geoip"
VOLUME "/var/azuracast/storage/rsas"
VOLUME "/var/azuracast/storage/sftpgo"
VOLUME "/var/azuracast/storage/shoutcast2"
VOLUME "/var/azuracast/storage/stereo_tool"
VOLUME "/var/azuracast/storage/uploads"

EXPOSE 80 443 2022
EXPOSE 8000-8999

# Sensible default environment variables.
ENV LANG="en_US.UTF-8" \
    PATH="${PATH}:/var/azuracast/storage/shoutcast2" \
    APPLICATION_ENV="production" \
    MYSQL_HOST="localhost" \
    MYSQL_PORT=3306 \
    MYSQL_USER="azuracast" \
    MYSQL_PASSWORD="azur4c457" \
    MYSQL_DATABASE="azuracast" \
    ENABLE_REDIS="true" \
    REDIS_HOST="localhost" \
    REDIS_PORT=6379 \
    REDIS_DB=0 \
    NGINX_RADIO_PORTS="default" \
    NGINX_WEBDJ_PORTS="default" \
    COMPOSER_PLUGIN_MODE="false" \
    ADDITIONAL_MEDIA_SYNC_WORKER_COUNT=0 \
    PROFILING_EXTENSION_ENABLED=0 \
    PROFILING_EXTENSION_ALWAYS_ON=0 \
    PROFILING_EXTENSION_HTTP_KEY=dev \
    PROFILING_EXTENSION_HTTP_IP_WHITELIST=* \
    ENABLE_WEB_UPDATER="true"

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

RUN composer install --no-ansi --no-interaction \
    && composer clear-cache

RUN npm ci --include=dev \
    && npm cache clean --force

USER root

# Sensible default environment variables.
ENV APPLICATION_ENV="development" \
    PROFILING_EXTENSION_ENABLED=1 \
    ENABLE_WEB_UPDATER="false"

# Entrypoint and default command
ENTRYPOINT ["tini", "--", "/usr/local/bin/my_init"]
CMD ["--no-main-command"]

#
# Final build (Just environment vars and squishing the FS)
#
FROM pre-final AS final

USER azuracast

WORKDIR /var/azuracast/www

COPY --chown=azuracast:azuracast . .

RUN composer install --no-dev --no-ansi --no-autoloader --no-interaction \
    && composer dump-autoload --optimize --classmap-authoritative \
    && composer clear-cache

USER root

# Entrypoint and default command
ENTRYPOINT ["tini", "--", "/usr/local/bin/my_init"]
CMD ["--no-main-command"]
