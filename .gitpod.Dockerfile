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
USER gitpod
ENV NODE_VERSION=16.10.0
ENV TRIGGER_REBUILD=1
RUN curl -fsSL https://raw.githubusercontent.com/nvm-sh/nvm/v0.38.0/install.sh | PROFILE=/dev/null bash \
    && bash -c ". .nvm/nvm.sh \
        && nvm install $NODE_VERSION \
        && nvm alias default $NODE_VERSION \
        && npm install -g typescript yarn node-gyp" \
    && echo ". ~/.nvm/nvm-lazy.sh"  >> /home/gitpod/.bashrc.d/50-node
# above, we are adding the lazy nvm init to .bashrc, because one is executed on interactive shells, the other for non-interactive shells (e.g. plugin-host)
COPY --chown=gitpod:gitpod nvm-lazy.sh /home/gitpod/.nvm/nvm-lazy.sh
ENV PATH=$PATH:/home/gitpod/.nvm/versions/node/v${NODE_VERSION}/bin

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
