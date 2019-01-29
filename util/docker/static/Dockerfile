FROM library/node:8-alpine

RUN apk update \
    && apk add bash

RUN mkdir -p /data/node_modules \
    && chown -R node:node /data

COPY build_entrypoint.sh /
RUN chmod a+x /build_entrypoint.sh

# Define working directory.
WORKDIR /data

# Define working user.
USER node

VOLUME /data/node_modules

# Define default command.
ENTRYPOINT ["/build_entrypoint.sh"]
CMD ["npm", "run", "build"]
