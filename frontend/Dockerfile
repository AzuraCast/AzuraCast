FROM library/node:lts-alpine

RUN apk update \
    && apk add bash python make g++

RUN mkdir -p /data/frontend/node_modules /data/resources/locale /data/web/static \
    && chown -R node:node /data

COPY build_entrypoint.sh /
RUN chmod a+x /build_entrypoint.sh

# Define working directory.
WORKDIR /data/frontend

# Define working user.
USER node

VOLUME /data/frontend/node_modules

# Define default command.
ENTRYPOINT ["/build_entrypoint.sh"]
CMD ["npm", "run", "build"]
