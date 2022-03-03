#
# Icecast build stage (for later copy)
#
FROM ghcr.io/azuracast/icecast-kh-ac:2.4.0-kh15-ac2 AS icecast

#
# Golang dependencies build step
#
FROM golang:1.17-buster AS dockerize

RUN apt-get update \
    && apt-get install -y --no-install-recommends openssl git

RUN go install github.com/jwilder/dockerize@latest

#
# Final build image
#
FROM ubuntu:focal

ENV TZ="UTC"

# Add Dockerize
COPY --from=dockerize /go/bin/dockerize /usr/local/bin

# Import Icecast-KH from build container
COPY --from=icecast /usr/local/bin/icecast /usr/local/bin/icecast
COPY --from=icecast /usr/local/share/icecast /usr/local/share/icecast

# Run base build process
COPY ./util/docker/web /bd_build/

RUN chmod a+x /bd_build/*.sh \
    && /bd_build/prepare.sh \
    && /bd_build/add_user.sh \
    && /bd_build/setup.sh \
    && /bd_build/cleanup.sh \
    && rm -rf /bd_build

#
# START Operations as `azuracast` user
#
USER azuracast

WORKDIR /var/azuracast/www

COPY --chown=azuracast:azuracast ./composer.json ./composer.lock ./
RUN composer install \
    --no-dev \
    --no-ansi \
    --no-autoloader \
    --no-interaction

COPY --chown=azuracast:azuracast . .

RUN composer dump-autoload --optimize --classmap-authoritative \
    && touch /var/azuracast/.docker

VOLUME ["/var/azuracast/www_tmp", "/var/azuracast/uploads", "/var/azuracast/backups", "/var/azuracast/sftpgo/persist"]

#
# END Operations as `azuracast` user
#
USER root

EXPOSE 80 2022
EXPOSE 8000-8999

# Include radio services in PATH
ENV PATH="${PATH}:/var/azuracast/servers/shoutcast2"
VOLUME ["/var/azuracast/servers/shoutcast2"]

# MariaDB Data
VOLUME ["/var/lib/mysql"]

# Sensible default environment variables.
ENV LANG="en_US.UTF-8" \
    DOCKER_IS_STANDALONE="true" \
    APPLICATION_ENV="production" \
    ENABLE_ADVANCED_FEATURES="false" \
    MYSQL_HOST="mariadb" \
    MYSQL_PORT=3306 \
    MYSQL_USER="azuracast" \
    MYSQL_PASSWORD="azur4c457" \
    MYSQL_DATABASE="azuracast" \
    ENABLE_REDIS="true" \
    REDIS_HOST="redis" \
    REDIS_PORT=6379 \
    REDIS_DB=1 \
    NGINX_RADIO_PORTS="default" \
    NGINX_WEBDJ_PORTS="default" \
    PREFER_RELEASE_BUILDS="false" \
    COMPOSER_PLUGIN_MODE="false" \
    ADDITIONAL_MEDIA_SYNC_WORKER_COUNT=0 \
    PROFILING_EXTENSION_ENABLED=0 \
    PROFILING_EXTENSION_ALWAYS_ON=0 \
    PROFILING_EXTENSION_HTTP_KEY=dev \
    PROFILING_EXTENSION_HTTP_IP_WHITELIST=*

# Entrypoint and default command
ENTRYPOINT ["/usr/local/bin/uptime_wait"]
CMD ["/usr/local/bin/my_init"]
