FROM azuracast/azuracast_web:latest

ENV PATH="${PATH}:/var/azuracast/.composer/vendor/bin"

RUN apt-get update \
    && apt-get install -q -y php7.2-xdebug

RUN mkdir -p /tmp/blackfire \
    && curl -A "Docker" -L https://blackfire.io/api/v1/releases/client/linux_static/amd64 | tar zxp -C /tmp/blackfire \
    && mv /tmp/blackfire/blackfire /usr/bin/blackfire \
    && rm -Rf /tmp/blackfire
