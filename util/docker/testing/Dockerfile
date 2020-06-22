FROM azuracast/azuracast_web_v2:latest

RUN apt-get update \
    && apt-get install -q -y php7.4-xdebug

ENV PATH="${PATH}:/var/azuracast/.composer/vendor/bin"

ENV LANG="en_US.UTF-8" \
    APPLICATION_ENV="testing"

USER root