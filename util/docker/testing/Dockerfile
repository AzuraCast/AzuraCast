FROM azuracast/azuracast_web:latest

ENV PATH="${PATH}:/var/azuracast/.composer/vendor/bin"

RUN apt-get update \
    && apt-get install -q -y php7.2-xdebug
