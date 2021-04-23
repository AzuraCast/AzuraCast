FROM php:8.0-cli-alpine

RUN apk add --no-cache npm

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

RUN mkdir /app
WORKDIR /app

USER root

ENV APPLICATION_ENV="testing"

COPY ./entrypoint.sh /entrypoint.sh

RUN chmod a+x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/bin/true"]
