#
# ------------------------------------------
# First base build stage (common dependencies)
# ------------------------------------------
#
FROM ubuntu:focal as build_base

ENV TZ="UTC"

# Run base build process
COPY ./util/docker/base/ /bd_build_base

RUN chmod a+x /bd_build_base/*.sh \
    && /bd_build_base/prepare.sh \
    && /bd_build_base/add_user.sh \
    && /bd_build_base/setup.sh \
    && /bd_build_base/cleanup.sh \
    && rm -rf /bd_build_base

#
# ------------------------------------------
# Second build stage (also used for testing)
# ------------------------------------------
#
FROM build_base AS build_final

# Run base build process
COPY ./util/docker/final/ /bd_build_final

RUN chmod a+x /bd_build_final/*.sh \
    && /bd_build_final/setup.sh \
    && /bd_build_final/cleanup.sh \
    && rm -rf /bd_build_final

# Sensible default environment variables.
ENV LANG="en_US.UTF-8" \
    APPLICATION_ENV="production" \
    AZURACAST_DOCKER_STANDALONE_MODE=0 \
    MYSQL_HOST="mariadb" \
    MYSQL_PORT=3306 \
    MYSQL_USER="azuracast" \
    MYSQL_PASSWORD="azur4c457" \
    MYSQL_DATABASE="azuracast" \
    PREFER_RELEASE_BUILDS="false" \
    COMPOSER_PLUGIN_MODE="false" \
    ADDITIONAL_MEDIA_SYNC_WORKER_COUNT=0 \
    PROFILING_EXTENSION_ENABLED=0 \
    PROFILING_EXTENSION_ALWAYS_ON=0 \
    PROFILING_EXTENSION_HTTP_KEY=dev \
    PROFILING_EXTENSION_HTTP_IP_WHITELIST=127.0.0.1

# START Operations as `azuracast` user
USER azuracast

RUN touch /var/azuracast/.docker
WORKDIR /var/azuracast/www

# END Operations as `azuracast` user
USER root

# Entrypoint and default command
ENTRYPOINT ["/usr/local/bin/uptime_wait"]
CMD ["/usr/local/bin/my_init"]

#
# ------------------------------------------
# Icecast build stage (for later copy)
# ------------------------------------------
#
FROM azuracast/icecast-kh-ac:2.4.0-kh15-ac1 AS build_icecast

#
# ------------------------------------------
# Liquidsoap build stage
# ------------------------------------------
#
FROM build_base AS build_liquidsoap

# Install build tools
RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -q -y --no-install-recommends \
        build-essential libssl-dev libcurl4-openssl-dev bubblewrap unzip m4 software-properties-common \
        ocaml opam \
        autoconf automake

USER azuracast

RUN opam init --disable-sandboxing -a --bare && opam switch create ocaml-system.4.08.1

# Uncomment to Pin specific commit of Liquidsoap
RUN cd ~/ \
     && git clone --recursive https://github.com/savonet/liquidsoap.git \
    && cd liquidsoap \
    && git checkout 75d530c86bf638e3c50c08b7802d92270288e31b \
    && opam pin add --no-action liquidsoap .

ARG opam_packages="ladspa.0.1.5 ffmpeg.0.4.3 samplerate.0.1.4 taglib.0.3.3 mad.0.4.5 faad.0.4.0 fdkaac.0.3.1 lame.0.3.3 vorbis.0.7.1 cry.0.6.1 flac.0.1.5 opus.0.1.3 duppy.0.8.0 ssl liquidsoap"
RUN opam install -y ${opam_packages}

#
# ------------------------------------------
# Final unified container build
# ------------------------------------------
#
FROM build_final

# Import Icecast-KH from build container
COPY --from=build_icecast /usr/local/bin/icecast /usr/local/bin/icecast
COPY --from=build_icecast /usr/local/share/icecast /usr/local/share/icecast

# Import Liquidsoap from build container
COPY --from=build_liquidsoap --chown=azuracast:azuracast /var/azuracast/.opam/ocaml-system.4.08.1 /var/azuracast/.opam/ocaml-system.4.08.1

RUN ln -s /var/azuracast/.opam/ocaml-system.4.08.1/bin/liquidsoap /usr/local/bin/liquidsoap

# Include radio services in PATH
ENV PATH="${PATH}:/var/azuracast/servers/shoutcast2"

# START Operations as `azuracast` user
USER azuracast

COPY --chown=azuracast:azuracast ./composer.json ./composer.lock ./
RUN composer install \
    --no-dev \
    --no-ansi \
    --no-autoloader \
    --no-interaction

COPY --chown=azuracast:azuracast . .

RUN composer dump-autoload --optimize --classmap-authoritative

# END Operations as `azuracast` user
USER root

VOLUME ["/var/azuracast/www_tmp", "/var/azuracast/stations", "/var/azuracast/backups", "/var/azuracast/sftpgo/persist", "/var/azuracast/servers/shoutcast2"]

EXPOSE 80 2022 8000-8999

# Nginx Proxy environment variables.
ENV VIRTUAL_HOST="azuracast.local" \
    HTTPS_METHOD="noredirect"
