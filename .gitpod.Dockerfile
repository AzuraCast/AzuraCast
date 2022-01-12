FROM gitpod/workspace-base:latest

### PHP ###
USER root

ENV PHP_VERSION=8.0

RUN add-apt-repository -y ppa:ondrej/php \
    && install-packages \
      php${PHP_VERSION}-cli php${PHP_VERSION}-gd \
      php${PHP_VERSION}-curl php${PHP_VERSION}-xml php${PHP_VERSION}-zip php${PHP_VERSION}-bcmath \
      php${PHP_VERSION}-gmp php${PHP_VERSION}-mysqlnd php${PHP_VERSION}-mbstring php${PHP_VERSION}-intl \
      php${PHP_VERSION}-redis php${PHP_VERSION}-maxminddb php${PHP_VERSION}-xdebug \
      mariadb-client \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

### Node.js ###
USER root
RUN curl -fsSL https://deb.nodesource.com/setup_16.x | sudo -E bash - \
  && install-packages nodejs

### Docker ###
USER root
# https://docs.docker.com/engine/install/ubuntu/
RUN curl -o /var/lib/apt/dazzle-marks/docker.gpg -fsSL https://download.docker.com/linux/ubuntu/gpg \
    && apt-key add /var/lib/apt/dazzle-marks/docker.gpg \
    && add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" \
    && install-packages docker-ce=5:19.03.15~3-0~ubuntu-focal docker-ce-cli=5:19.03.15~3-0~ubuntu-focal containerd.io

RUN curl -o /usr/local/bin/docker-compose -fsSL https://github.com/docker/compose/releases/download/1.29.2/docker-compose-Linux-x86_64 \
    && chmod +x /usr/local/bin/docker-compose

### End ###

ENV AZURACAST_PUID=33333
ENV AZURACAST_PGID=33333

USER gitpod
