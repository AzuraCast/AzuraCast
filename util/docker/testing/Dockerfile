FROM azuracast/azuracast_web_v2:latest

ENV PATH="${PATH}:/var/azuracast/.composer/vendor/bin"

RUN apt-get update \
    && apt-get install -q -y php7.4-xdebug