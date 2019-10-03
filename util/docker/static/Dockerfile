FROM library/node:8-alpine

RUN apk update \
    && apk add bash

RUN mkdir -p /data/web/static/node_modules /data/resources/locale \
    && chown -R node:node /data

COPY build_entrypoint.sh /
RUN chmod a+x /build_entrypoint.sh

# Define working directory.
WORKDIR /data/web/static

# Define working user.
USER node

VOLUME /data/web/static/node_modules

# Define default command.
ENTRYPOINT ["/build_entrypoint.sh"]
CMD ["npm", "run", "build"]
